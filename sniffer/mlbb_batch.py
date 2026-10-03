#!/usr/bin/env python3
"""Batch profile lookup via network replay - basic profile data (~7s per ID).
Usage: python3 mlbb_batch.py <id1> <id2> ...
   or: python3 mlbb_batch.py ids.txt (one ID per line)
"""
import socket, struct, json, sys, re, time
sys.path.insert(0, '.')
from mlbb_replay_v2 import encode_varint, frame_msgs, recv_all, do_handshake

def build_search_request(target_id, template_hex):
    raw = bytes.fromhex(template_hex)
    body = bytearray(raw[4:])
    j45 = body.find(b'\x45')
    inner_start = j45 + 2
    old_len = body[j45+1]
    inner = bytearray(body[inner_start:inner_start+old_len])
    trailer = bytes(body[inner_start+old_len:])
    tid = encode_varint(target_id)
    j = inner.find(b'\x70\x40\x00\x01')
    assert j >= 0
    k = j + 4
    e = k
    while inner[e] & 0x80: e += 1
    e += 1
    new_inner = inner[:k] + tid + inner[e:]
    new_body = bytes(body[:j45]) + b'\x45' + bytes([len(new_inner)]) + bytes(new_inner) + trailer
    total = 4 + len(new_body)
    return bytes([0x00, (total >> 16) & 0xff, (total >> 8) & 0xff, total & 0xff]) + new_body

def build_visit_request(target_id, zone, template_hex):
    raw = bytes.fromhex(template_hex)
    body = bytearray(raw[4:])
    j45 = body.find(b'\x45')
    inner_start = j45 + 2
    old_len = body[j45+1]
    inner = bytearray(body[inner_start:inner_start+old_len])
    trailer = bytes(body[inner_start+old_len:])
    tid = encode_varint(target_id)
    zid = encode_varint(zone)
    j = inner.find(b'\x70\x00')
    assert j >= 0
    k = j + 2
    e = k
    while inner[e] & 0x80: e += 1
    e += 1
    assert inner[e:e+1] == b'\x01'
    f = e + 1
    g = f
    while inner[g] & 0x80: g += 1
    g += 1
    new_inner = inner[:k] + tid + b'\x01' + zid + inner[g:]
    new_body = bytes(body[:j45]) + b'\x45' + bytes([len(new_inner)]) + bytes(new_inner) + trailer
    total = 4 + len(new_body)
    return bytes([0x00, (total >> 16) & 0xff, (total >> 8) & 0xff, total & 0xff]) + new_body

def decode_varint(buf, pos):
    v = 0; sh = 0
    while pos < len(buf):
        b = buf[pos]; v |= (b & 0x7f) << sh; sh += 7; pos += 1
        if not (b & 0x80): break
    return v, pos

def lookup_one(target_id, data, dctx):
    """Returns (result_dict, None) or (None, error_str)."""
    try:
        s, hresp = do_handshake(data['server'], data['port'], data['handshake'])
        if not hresp:
            return None, "handshake failed"
        sreq = build_search_request(target_id, data['search_request'])
        s.sendall(sreq)
        resp = recv_all(s, timeout=4)
        s.close()
        zone = None
        for typ, m in frame_msgs(resp):
            if typ != 0x10: continue
            try:
                blob = dctx.decompress(m, max_output_size=10_000_000)
            except: continue
            tid = encode_varint(target_id)
            i = blob.find(tid)
            if i >= 0:
                j = i + len(tid)
                if blob[j:j+1] == b'\x01':
                    z, _ = decode_varint(blob, j+1)
                    zone = z
                    break
        if not zone:
            return None, "search failed / zone not found"
        time.sleep(0.3)
        s, hresp = do_handshake(data['server'], data['port'], data['handshake'])
        if not hresp:
            return None, "handshake failed (visit)"
        vreq = build_visit_request(target_id, zone, data['profile_request'])
        s.sendall(vreq)
        resp = recv_all(s, timeout=5)
        s.close()
        name, avatar = None, None
        for typ, m in frame_msgs(resp):
            if typ != 0x10: continue
            try:
                blob = dctx.decompress(m, max_output_size=10_000_000)
            except: continue
            strs = re.findall(rb'[\x20-\x7e]{4,}', blob)
            for x in strs:
                t = x.decode()
                if 'dist/' in t and not avatar:
                    avatar = t
                elif not name and len(t) < 50 and 'dist/' not in t:
                    if not any(c in t for c in ['|', '@']):
                        name = t
            break
        return {'id': target_id, 'zone': zone, 'name': name, 'avatar': avatar}, None
    except Exception as e:
        return None, str(e)

def main():
    if len(sys.argv) < 2:
        print("Usage: python3 mlbb_batch.py <id1> <id2> ... | ids.txt")
        return
    ids = []
    for a in sys.argv[1:]:
        if a.endswith('.txt'):
            ids += [int(x.strip()) for x in open(a) if x.strip().isdigit()]
        elif a.isdigit():
            ids.append(int(a))
    if not ids:
        print("no valid IDs"); return

    data = json.load(open('replay_data.json'))
    try:
        import zstandard
        dctx = zstandard.ZstdDecompressor()
    except ImportError:
        print("pip install zstandard"); return

    print(f"Looking up {len(ids)} IDs...")
    results = []
    t0 = time.time()
    for i, tid in enumerate(ids):
        t1 = time.time()
        res, err = lookup_one(tid, data, dctx)
        dt = time.time() - t1
        if err:
            print(f"[{i+1}/{len(ids)}] {tid}: FAILED ({err}) [{dt:.1f}s]")
            results.append({'id': tid, 'error': err})
        else:
            print(f"[{i+1}/{len(ids)}] {tid}: {res['name']} (zone {res['zone']}) [{dt:.1f}s]")
            results.append(res)
        if i < len(ids) - 1:
            time.sleep(1)

    total = time.time() - t0
    ok = len([r for r in results if 'error' not in r])
    print(f"\nDone: {ok}/{len(ids)} OK in {total:.1f}s")
    json.dump(results, open('batch_results.json', 'w'), indent=2)
    print("Saved to batch_results.json")

if __name__ == '__main__':
    main()
