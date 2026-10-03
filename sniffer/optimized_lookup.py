def execute_in_game_lookup(target_id: str, pcap_path: str):
    """Executes verified touch sequence across all profile pages and captures packets.
    OPTIMIZED: Reduced sleeps from 14.6s to ~8.5s total. Test and adjust if UI needs more time.
    """
    ensure_lobby_state()

    if os.path.exists(pcap_path):
        try:
            os.remove(pcap_path)
        except Exception:
            pass

    if_idx = find_tshark_interface()
    tshark_proc = subprocess.Popen([
        TSHARK_BIN,
        "-i", if_idx,
        "-f", "tcp portrange 30100-30120",
        "-w", pcap_path
    ])
    time.sleep(0.3)  # was 0.6

    try:
        del_keys = " ".join(["67"] * 25)
        # 1. Tap Add Friends icon at (1460, 615)
        tap(1460, 615)
        time.sleep(0.5)  # was 0.8
        # 2. Tap search input box at (400, 220)
        tap(400, 220)
        time.sleep(0.5)  # was 0.8
        # Clear input box via keyevent 123 + backspaces
        adb_cmd("shell", f"input keyevent 123 {del_keys}")
        time.sleep(0.1)  # was 0.2
        # Type target player ID
        adb_cmd("shell", f"input text {target_id}")
        time.sleep(0.2)  # was 0.3
        # Commit input via Android soft keyboard green checkmark (1479, 788) + Enter
        tap(1479, 788)
        time.sleep(0.1)  # was 0.2
        adb_cmd("shell", "input keyevent 66")
        time.sleep(0.2)  # was 0.4
        # 3. Tap ID Search button at (1310, 220)
        tap(1310, 220)
        time.sleep(1.0)  # was 1.5

        # 4. Tap search result player avatar box at (240, 360)
        tap(240, 360)
        time.sleep(0.8)  # was 1.2

        # 5. Open Personal Zone (tap Magnifying Glass button at verified center 1232, 636)
        tap(1232, 636)
        time.sleep(1.5)  # was 2.5 - skin data loads here

        # 6. Open Battlefield tab on sidebar at (80, 350)
        tap(80, 350, 200)
        time.sleep(1.0)  # was 1.8

        # 7. Tap Favorite Heroes on sidebar at (80, 400)
        tap(80, 400, 200)
        time.sleep(1.0)  # was 1.8

        # 8. Clean return to lobby (optimized: fewer sleeps)
        tap(75, 45)
        time.sleep(0.4)  # was 0.6
        tap(75, 45)
        time.sleep(0.4)  # was 0.6
        tap(100, 450)
        time.sleep(0.3)  # was 0.5
        tap(1395, 120)
        time.sleep(0.3)  # was 0.5
        tap(100, 450)
        time.sleep(0.2)  # was 0.3

    finally:
        tshark_proc.terminate()
        try:
            tshark_proc.wait(timeout=3)
        except subprocess.TimeoutExpired:
            tshark_proc.kill()

    ensure_lobby_state()
