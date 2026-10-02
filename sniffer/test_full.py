#!/usr/bin/env python3
"""Test the reversed-order request (c2s[13]) that likely returns full profile data."""
import socket, struct, json, sys, re
sys.path.insert(0, '.')
from mlbb_replay_v2 import encode_varint, frame_msgs, recv_all, do_handshake

# c2s[13] template: 70008d9a0101c68c01450a7000b94801b389fd738080
# Structure: 70 00 8d 9a 01 01 c6 8c 01 45 0a 70 00 0b <zone> 01 <id> 80 80
TEMPLATE = '70008d9a0101c68c01450a7000b94801b389fd738080'

def build_reversed_request(target_id, zone):
    # TEMPLATE has NO frame header (it's the raw message)
    raw = bytes.fromhex(TEMPLATE)
    body = bytearray(raw)  # entire template is the message body
    j45 = body.find(b'\x45')
    inner_start = j45 + 2
    old_len = body[j45+1]
    inner = bytearray(body[inner_start:inner_start+old_len])
    trailer = bytes(body[inner_start+old_len:])

    # Find 70 00 <zone> 01 <id> (reversed order: zone first!)
    # Template inner: 70 00 b948 01 b389fd73 80
    j = inner.find(b'\x70\x00' + encode_varint(9273))
    assert j >= 0, "70 00 + zone not found"
    k = j + 2  # start of zone varint
    e = k
    while inner[e] & 0x80:
        e += 1
    e += 1
    # should be 01 then id varint
    assert inner[e:e+1] == b'\x01', f"expected 01 at {e}, got {inner[e:e+1].hex()}"
    f = e + 1
    g = f
    while inner[g] & 0x80:
        g += 1
    g += 1
    # Replace zone and id
    new_inner = inner[:k] + encode_varint(zone) + b'\x01' + encode_varint(target_id) + inner[g:]
    new_body = bytes(body[:j45]) + b'\x45' + bytes([len(new_inner)]) + bytes(new_inner) + trailer
    total = 4 + len(new_body)
    return bytes([0x00, (total >> 16) & 0xff, (total >> 8) & 0xff, total & 0xff]) + new_body

def main():
    target = 776114101
    zone = 4280
    data = json.load(open('replay_data.json'))
    try:
        import zstandard
        dctx = zstandard.ZstdDecompressor()
    except ImportError:
        print("need zstandard"); return

    print("=== Testing reversed-order request (full data?) ===")
    s, hresp = do_handshake(data['server'], data['port'], data['handshake'])
    if not hresp:
        print("handshake failed"); return
    req = build_reversed_request(target, zone)
    print(f"req ({len(req)}B): {req.hex()}")
    s.sendall(req)
    resp = recv_all(s, timeout=15)
    s.close()
    print(f"response: {len(resp)} bytes")
    for typ, m in frame_msgs(resp):
        if typ != 0x10:
            print(f"  type=0x{typ:02x} len={len(m)}")
            continue
        try:
            blob = dctx.decompress(m, max_output_size=50_000_000)
        except Exception as e:
            print(f"  type=0x10 decomp failed: {e}")
            continue
        print(f"  type=0x10 decomp={len(blob)}B")
        # Check for skin data
        ts_varint = encode_varint(1553878453)  # known timestamp from 243221683
        # For 776114101, we don't know the timestamp, but let's look for skin-like patterns
        # Count varints that look like skin IDs (1000-2000 range)
        strs = re.findall(rb'[\x20-\x7e]{4,}', blob)
        print(f"  strings: {len(strs)}")
        for x in strs[:10]:
            t = x.decode()
            if 'dist/' not in t and len(t) < 80:
                print(f"    {t[:60]}")
        # Save for analysis
        open(f'full_{target}.bin', 'wb').write(blob)
        print(f"  saved full_{target}.bin ({len(blob)}B)")

if __name__ == '__main__':
    main()
