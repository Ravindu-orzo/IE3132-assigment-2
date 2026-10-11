# AVALON CTF
## 1. SETUP and RUNNING the CTF platform (Docker image)

1. Simply extract the "AVALON CTF" zip.
2. Make sure docker desktop is running
3. Open powershell within the extracted folder "AVALON CTF" and run `docker compose up --build`
4. Open `localhost:8080/` on your web browser

## 2. Flags for all stages:
| Stage No. | Stage Name             | Flag                                    |
| :-------: | ---------------------- | --------------------------------------- |
|     01    | Hidden in plainsight   | `AVALON{tHe_D00R_Ha$_App3ArEd}`         |
|     02    | Can’t fake permissions | `AVALON{gr3pping_a1nt_th4t_h4rd_I$_1t}` |
|     03    | The archivist’s secret | `AVALON{N0_0n3_L3ft_Th3_T0w3r}`         |
|     04    | Crack in the armor     | `AVALON{th4t_mu5t_h4v3_been_hard}`      |
|     05    | Captured login         | `AVALON{th4t_1s_why_y0u_3nabl3_http5}`  |
|     06    | Encrypted secrets      | `AVALON{w4tch_br3aking_b4d_1ts_g00d}`   |
|     07    | The unwritten trail    | `AVALON{Av4l0n_W4s_N3v3r_F0rg0tt3n}`    |

### 3. Automation/solver scripts (IT24103645 & IT24102401)

| Stage Number and Name         | Script Name                | How to Run and What It Does                                                                                                                                                                                                                                                                                                                                                                         | Made By                        |
| ----------------------------- | -------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ------------------------------ |
| 01 - Hidden in the plainsight | `stage01_steganography.py` | How to run: Will be added later.                                                                                                                                                                                                                                                                                                                                                                    | IT24102401 Jayathissa T.D.A.S. |
| 04 - Crack in the armor       | `crack_in_the_armor.sh`    | **How to run:**<br>1. Place this script in the same folder as the protected ZIP file and the wordlist.<br>2. Make the script executable: `chmod +x crack_in_the_armor.sh`<br>3. Run the script: `./crack_in_the_armor.sh protected.zip wordlist-surnames-SL.txt`<br><br>**What it does:**<br>Extracts the password hash from the ZIP file, then searches the wordlist for a matching password hash. | IT24103645 Wijayarathna P.R.M. |




### 4. Test scripts (IT24103309 & IT24103973)

| Script Name | What It Does | How to Run It | Made By                         |
| ----------- | ------------ | ------------- | ------------------------------- |
|             |              |               | IT24103309 Daskon W.W.D.A.M.P.B |
|             |              |               | IT24103973 Sirimanna H.T.       |

### 5. Stage description

| Stage | Title | Domain | Difficulty | What the Player Has to Do |
|-------|-------|--------|------------|---------------------------|
| 01 | Hidden in the Plain Sight | Steganography | Easy | Inspect an image's metadata to find a hidden hex string, decode it, reverse the flag, and submit it. |
| 02 | Can't Fake Permissions | Linux / System Security | Easy | SSH into a VM, use `ls -l` to find the single binary with owner execute permission, then grep it for the flag. |
| 03 | The Archivist's Secret | Cryptography | Easy | Copy apparently redacted text from a PDF, reconstruct a broken Base64 string, decode it, then apply a Caesar shift of 7. |
| 04 | Crack in the Armor | Cryptography | Medium | Extract a ZIP hash with zip2john, crack it using John the Ripper and a surname wordlist, then extract `data.txt` for the flag. |
| 05 | Captured Login | Digital Forensics / Networking | Medium | Open a PCAPNG in Wireshark, filter HTTP POST traffic, recover stolen credentials, log into the website, and read the flag. |
| 06 | Encrypted Secrets | Reverse Engineering | Hard | Analyze an `Encrypter` binary with `strings` or Ghidra to recover the hardcoded XOR key, then decrypt the message for the flag. |
| 07 | The Unwritten Trail | OSINT | Hard | Follow a chain of linked clues (leaked username, breach dump, archived profiles, link-in-bio) to reach the final Instagram profile containing the flag. |
