#!/usr/bin/env python3

import subprocess
import re
import sys


def solve_stage01(image_path):
    print("[+] Analyzing image with zsteg...")

    try:
        result = subprocess.run(
            ["zsteg", "-a", image_path],
            capture_output=True,
            check=True
        )
    except FileNotFoundError:
        print("[-] Error: zsteg is not installed.")
        sys.exit(1)
    except subprocess.CalledProcessError as e:
        print("[-] zsteg failed:", e.stderr.decode(errors="replace"))
        sys.exit(1)

    # Decode output safely; zsteg may produce non-UTF-8 bytes
    output = result.stdout.decode("utf-8", errors="replace")

    # Find the hidden hexadecimal string
    pattern = (
        r'b1,rgb,lsb,xy\s+\.\.\s+text:\s*"([0-9a-fA-F]+)"'
    )
    match = re.search(pattern, output)

    if not match:
        print("[-] Hex string not found automatically.")
        print(output)
        hex_string = input("Enter the hex string manually: ").strip()
    else:
        hex_string = match.group(1)
        print("[+] Hidden hex string:", hex_string)

    try:
        # Decode hexadecimal into text
        decoded_text = bytes.fromhex(hex_string).decode("utf-8")

        # Reverse the decoded text and remove padding spaces
        flag = decoded_text[::-1].strip()

        print("[+] Decoded text:", repr(decoded_text))
        print("[+] Reversed result:", flag)

    except (ValueError, UnicodeDecodeError) as e:
        print("[-] Decoding error:", e)
        sys.exit(1)


if __name__ == "__main__":
    if len(sys.argv) != 2:
        print(f"Usage: python3 {sys.argv[0]} <image.png>")
        sys.exit(1)

    solve_stage01(sys.argv[1])
