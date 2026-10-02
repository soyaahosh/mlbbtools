#!/usr/bin/env python3
"""
MLBB profile replay v2 — two-step flow (no hardcoded zone)
1. Handshake (replayed)
2. SEARCH request (70 40) for target ID -> response contains zone
3. PROFILE VISIT request (70 00) with target ID + zone -> detailed data

Usage: python3 mlbb_replay_v2.py <target_id>
Needs: replay_data.json, python3-zstandard
"""
import socket, struct, json, sys, time, re

def encode_varint(n):
    out = b''
    while True:
        b = n & 0x7f
        n >>= 7
        out += bytes([b | (0x80 if n else 0)])
        if not n:
            break
    return out

def decode_varint(buf, pos):
    v = 0; sh = 0
    while True:
        b = buf[pos]; v |= (b & 0x7f) << sh; sh += 7; pos += 1
        if not (b & 0x80):
            break
    return v, pos

def frame_msgs(blob):
    msgs = []
    off = 0
    while off + 4 <= len(blob):
        typ = blob[off]
        ln = struct.unpack('>I', b'\x00' + blob[off+1:off+4])[0]
        if ln < 4 or off + ln > len(blob):
            break
        msgs.append((typ, blob[off+4:off+ln]))
        off += ln
    return msgs

def swap_varint_after(body, marker, target_id):
    """Replace varint following marker bytes; return new body."""
    i = body.find(marker)
    assert i >= 0, 'marker not found'
    j = i + len(marker)
    k = j
    while body[k] & 0x80:
        k += 1
    k += 1
    return body[:j] + encode_varint(target_id) + body[k:]

def build_search_request(target_id, template_hex):
    raw = bytes.fromhex(template_hex)
    body = bytearray(raw[4:])
    # body: 70 00 <seq> 01 <subseq> 45 09 [70 40 00 01 <varint:id> 80] 80
    j45 = body.find(b'\x45')
    inner_start = j45 + 2
    old_len = body[j45+1]
    inner = bytearray(body[inner_start:inner_start+old_len])
    trailer = bytes(body[inner_start+old_len:])
    # inner: 70 40 00 01 <varint:id> 80
    new_inner = swap_varint_after(inner, b'\x70\x40\x00\x01', target_id)
    new_body = bytes(body[:j45]) + b'\x45' + bytes([len(new_inner)]) + bytes(new_inner) + trailer
    total = 4 + len(new_body)
    return bytes([0x00, (total >> 16) & 0xff, (total >> 8) & 0xff, total & 0xff]) + new_body

def build_visit_request(target_id, zone, template_hex):
    raw = bytes.fromhex(template_hex)
    body = bytearray(raw[4:])
    # body: 70 00 <seq> 01 <subseq> 45 0a [70 00 <varint:id> 01 <varint:zone> 80] 80
    j45 = body.find(b'\x45')
    inner_start = j45 + 2
    old_len = body[j45+1]
    inner = bytearray(body[inner_start:inner_start+old_len])
    trailer = bytes(body[inner_start+old_len:])
    # inner: 70 00 <varint:id> 01 <varint:zone> 80
    new_inner = swap_varint_after(inner, b'\x70\x00', target_id)
    # zone follows: find 01 <varint> after the id
    j = new_inner.find(encode_varint(target_id)) + len(encode_varint(target_id))
    assert new_inner[j:j+1] == b'\x01', 'zone tag mismatch'
    k = j + 1
    e = k
    while new_inner[e] & 0x80:
        e += 1
    e += 1
    new_inner = new_inner[:k] + encode_varint(zone) + new_inner[e:]
    new_body = bytes(body[:j45]) + b'\x45' + bytes([len(new_inner)]) + bytes(new_inner) + trailer
    total = 4 + len(new_body)
    return bytes([0x00, (total >> 16) & 0xff, (total >> 8) & 0xff, total & 0xff]) + new_body

def find_zone(blob, target_id):
    """In decompressed search response: 70 00 <varint:id> 01 <varint:zone>"""
    pat = b'\x70\x00' + encode_varint(target_id) + b'\x01'
    i = blob.find(pat)
    if i < 0:
        return None
    zone, _ = decode_varint(blob, i + len(pat))
    return zone

def recv_all(s, timeout=8):
    s.settimeout(timeout)
    data = b''
    try:
        while True:
            chunk = s.recv(65536)
            if not chunk:
                break
            data += chunk
    except socket.timeout:
        pass
    return data

def main():
    if len(sys.argv) < 2:
        print("usage: python3 mlbb_replay_v2.py <target_id>")
        sys.exit(1)
    target = int(sys.argv[1])
    data = json.load(open('replay_data.json'))
    print(f"[+] target: {target}")

    s = socket.create_connection((data['server'], data['port']), timeout=10)
    print("[+] handshake ...")
    for hx in data['handshake']:
        s.sendall(bytes.fromhex(hx))
        time.sleep(0.15)
    resp = recv_all(s, timeout=5)
    print(f"[+] handshake ok ({len(resp)} bytes back)")
    if not resp:
        print("[-] no handshake response; token may be expired")
        return

    # step 1: search
    sreq = build_search_request(target, data['search_request'])
    print(f"[+] search req ({len(sreq)}B): {sreq.hex()}")
    s.sendall(sreq)
    resp = recv_all(s, timeout=10)
    print(f"[+] search response: {len(resp)} bytes")
    try:
        import zstandard
        dctx = zstandard.ZstdDecompressor()
    except ImportError:
        print("[-] pip install zstandard")
        return
    zone = None
    for typ, m in frame_msgs(resp):
        blob = dctx.decompress(m, max_output_size=50_000_000) if typ == 0x10 else m
        z = find_zone(blob, target)
        if z:
            zone = z
            break
    if zone is None:
        print("[-] zone not found in search response (invalid ID?)")
        print(f"    raw: {resp[:100].hex()}")
        return
    print(f"[+] zone = {zone}")

    # step 2: profile visit with zone
    vreq = build_visit_request(target, zone, data['profile_request'])
    print(f"[+] visit req ({len(vreq)}B): {vreq.hex()}")
    s.sendall(vreq)
    resp = recv_all(s, timeout=10)
    s.close()
    print(f"[+] visit response: {len(resp)} bytes")
    found = False
    for typ, m in frame_msgs(resp):
        if typ != 0x10:
            continue
        try:
            blob = dctx.decompress(m, max_output_size=50_000_000)
        except Exception:
            continue
        strs = re.findall(rb'[\x20-\x7e]{4,}', blob)
        interesting = [x for x in strs if b'dist/' in x]
        if interesting:
            found = True
            print(f"    [+] type=0x10 len={len(blob)} profile data!")
            for x in interesting[:8]:
                print(f"        {x[:110]}")
    print("[+] SUCCESS" if found else "[-] no profile data in visit response")

if __name__ == '__main__':
    main()
