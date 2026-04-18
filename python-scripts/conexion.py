from LoRaRF import SX127x
import time
import re
import mysql.connector

# ── Configuración LoRa ────────────────────────────────────────────────────────
LoRa = SX127x()
LoRa.begin()

LORA_FREQUENCY   = 433000000
LORA_SPREADING   = 7
LORA_BANDWIDTH   = 125000
LORA_CODING_RATE = 5
LORA_PREAMBLE    = 8
LORA_SYNC_WORD   = 0x12

LoRa.setFrequency(LORA_FREQUENCY)
LoRa.setLoRaModulation(LORA_SPREADING, LORA_BANDWIDTH, LORA_CODING_RATE, False)
LoRa.setLoRaPacket(LoRa.HEADER_EXPLICIT, LORA_PREAMBLE, 255, True, False)
LoRa.setSyncWord(LORA_SYNC_WORD)

# ── Configuración MariaDB ─────────────────────────────────────────────────────
DB_CONFIG = {
    "host":     "127.0.0.1",
    "port":     3306,
    "user":     "root",
    "password": "admin123!",
    "database": "universidad",
    "charset":  "utf8mb4",
}

# ── Parseo del paquete ────────────────────────────────────────────────────────
def parse_packet(raw: str):
    """
    Formatos:
      0101|C|00000004D|B:85%   → tipo C, contenido = identificador profesor
      0101|M|AA:BB:CC:DD|B:72% → tipo M, contenido = UID bruto
    Devuelve (numero_aula, tipo, contenido, bat_pct) o None si formato inválido.
    """
    partes = raw.strip().split("|")
    if len(partes) != 4:
        return None
    numero_aula, tipo, contenido, bat_str = partes
    m = re.search(r"B:(\d+)%", bat_str)
    bat_pct = int(m.group(1)) if m else -1
    return numero_aula, tipo, contenido, bat_pct


# ── Upsert nodo + registrar asistencia ───────────────────────────────────────
def procesar_paquete(numero_aula: str, identificador_profesor: str, bat_pct: int):
    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cur  = conn.cursor()

        # 1. Buscar el aula por numero_aula
        cur.execute("SELECT id FROM aulas WHERE numero_aula = %s LIMIT 1", (numero_aula,))
        fila_aula = cur.fetchone()
        if not fila_aula:
            print(f"  [WARN] Aula '{numero_aula}' no existe en la BD. Ignorando.")
            cur.close()
            conn.close()
            return
        aula_id = fila_aula[0]

        # 2. Upsert en tabla nodos:
        #    - Si ya existe un nodo para ese aula_id → actualiza ultima_conexion y bateria
        #    - Si no existe → lo crea
        cur.execute("SELECT id FROM nodos WHERE aula_id = %s LIMIT 1", (aula_id,))
        fila_nodo = cur.fetchone()

        if fila_nodo:
            cur.execute("""
                UPDATE nodos
                SET ultima_conexion = NOW(),
                    bateria         = %s,
                    numero_aula     = %s
                WHERE aula_id = %s
            """, (bat_pct, numero_aula, aula_id))
            print(f"  [NODO] Actualizado: aula_id={aula_id} | bat={bat_pct}%")
        else:
            cur.execute("""
                INSERT INTO nodos (aula_id, numero_aula, ultima_conexion, bateria)
                VALUES (%s, %s, NOW(), %s)
            """, (aula_id, numero_aula, bat_pct))
            print(f"  [NODO] Creado nuevo nodo: aula_id={aula_id} | bat={bat_pct}%")

        conn.commit()

        # 3. Llamar al procedimiento de asistencia
        cur.callproc("registrar_asistencia", [identificador_profesor, numero_aula])

        for result in cur.stored_results():
            fila = result.fetchone()
            if fila:
                cols = [d[0] for d in result.description]
                print("  [DB]", dict(zip(cols, fila)))

        conn.commit()
        cur.close()
        conn.close()

    except mysql.connector.Error as e:
        print(f"  [DB ERROR] {e.msg}")


# ── Bucle principal ───────────────────────────────────────────────────────────
print("Receptor LoRa iniciado. Esperando paquetes...\n")

while True:
    try:
        LoRa.request()
        LoRa.wait()

        payload = []
        while LoRa.available() > 0:
            payload.append(LoRa.read())

        if not payload:
            continue

        rssi = LoRa.packetRssi()
        snr  = LoRa.snr()
        raw  = bytes(payload).decode("utf-8", errors="replace")
        print(f"[RX] RSSI={rssi} dBm | SNR={snr} dB | '{raw}'")

        resultado = parse_packet(raw)
        if resultado is None:
            print("  [WARN] Formato no reconocido. Ignorando.")
            continue

        numero_aula, tipo, contenido, bat_pct = resultado
        print(f"  Aula={numero_aula} | Tipo={tipo} | Contenido={contenido} | Bat={bat_pct}%")

        if tipo == "C":
            procesar_paquete(numero_aula, contenido, bat_pct)
        elif tipo == "M":
            print("  [INFO] Paquete tipo M (solo UID). No se registra asistencia.")
        else:
            print(f"  [WARN] Tipo '{tipo}' desconocido.")

    except KeyboardInterrupt:
        print("\nReceptor detenido.")
        break
    except Exception as e:
        print(f"[ERROR] {e}")
        time.sleep(1)