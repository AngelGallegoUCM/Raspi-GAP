from LoRaRF import SX127x
import time
import re
import mysql.connector
from Crypto.Cipher import AES

#  Configuración LoRa 
LoRa = SX127x()
LoRa.begin()

LORA_FREQUENCY   = 433000000
LORA_SPREADING   = 12
LORA_BANDWIDTH   = 125000
LORA_CODING_RATE = 8
LORA_PREAMBLE    = 12
LORA_SYNC_WORD   = 0x12

LoRa.setFrequency(LORA_FREQUENCY)
LoRa.setLoRaModulation(LORA_SPREADING, LORA_BANDWIDTH, LORA_CODING_RATE, False)
LoRa.setLoRaPacket(LoRa.HEADER_EXPLICIT, LORA_PREAMBLE, 255, True, False)
LoRa.setSyncWord(LORA_SYNC_WORD)

#  Clave AES-128 
AES_KEY = bytes([
    0x41, 0x53, 0x45, 0x47, 0x55, 0x52, 0x41, 0x44,
    0x4F, 0x5F, 0x31, 0x32, 0x38, 0x5F, 0x42, 0x49
])

#  Configuración MariaDB 
DB_CONFIG = {
    "host":     "127.0.0.1",
    "port":     3306,
    "user":     "root",
    "password": "admin123!",
    "database": "universidad",
    "charset":  "utf8mb4",
}


#  Descifrado AES-128-CBC 
def decrypt_packet(raw_bytes: bytes) -> str | None:
    """
    El nodo envía: [IV (16 bytes)] + [Datos cifrados AES-CBC (múltiplo de 16)]
    Devuelve el texto plano sin padding, o None si falla.
    """
    if len(raw_bytes) < 32:
        print(f"  [WARN] Paquete demasiado corto para ser cifrado: {len(raw_bytes)} bytes")
        return None

    iv          = raw_bytes[:16]
    cipher_data = raw_bytes[16:]

    if len(cipher_data) % 16 != 0:
        print(f"  [WARN] Datos cifrados no alineados a 16 bytes: {len(cipher_data)} bytes")
        return None

    try:
        cipher    = AES.new(AES_KEY, AES.MODE_CBC, iv)
        decrypted = cipher.decrypt(cipher_data)
        plaintext = decrypted.rstrip(b'\x00').decode("utf-8", errors="replace")
        return plaintext
    except Exception as e:
        print(f"  [WARN] Error al descifrar: {e}")
        return None


#  Parseo del paquete 
def parse_packet(plaintext: str):
    """
    Formato esperado: "<numero_aula>|<identificador_profesor>|B:<bat>%"
    Ejemplo: "0101|00000004D|B:85%"
    Devuelve (numero_aula, identificador_profesor, bat_pct) o None si el formato no es válido.
    """
    partes = plaintext.strip().split("|")

    if len(partes) != 3:
        return None

    numero_aula, identificador_profesor, bat_str = partes
    m = re.search(r"B:(\d+)%", bat_str)
    bat_pct = int(m.group(1)) if m else -1

    return numero_aula, identificador_profesor, bat_pct


#  Upsert nodo + registrar asistencia 
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

        # 2. Upsert en tabla nodos
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


#  Bucle principal 
print("Receptor LoRa iniciado. Esperando paquetes cifrados...\n")

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
        raw_bytes = bytes(payload)
        print(f"[RX] RSSI={rssi} dBm | SNR={snr} dB | {len(raw_bytes)} bytes cifrados")

        #  Descifrar 
        plaintext = decrypt_packet(raw_bytes)
        if plaintext is None:
            print("  [WARN] No se pudo descifrar el paquete. Ignorando.")
            continue

        print(f"  [DEC] '{plaintext}'")

        #  Parsear 
        resultado = parse_packet(plaintext)
        if resultado is None:
            print("  [WARN] Formato no reconocido tras descifrar. Ignorando.")
            continue

        numero_aula, identificador_profesor, bat_pct = resultado
        print(f"  Aula={numero_aula} | Profesor={identificador_profesor} | Bat={bat_pct}%")

        #  Procesar 
        procesar_paquete(numero_aula, identificador_profesor, bat_pct)

    except KeyboardInterrupt:
        print("\nReceptor detenido.")
        break
    except Exception as e:
        print(f"[ERROR] {e}")