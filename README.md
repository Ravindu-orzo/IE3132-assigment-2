# AVALON CTF
## 1. SETUP and RUNNING the CTF platform (Docker image)

1. Simply extract the "AVALON CTF" zip.
2. Make sure docker desktop is running
3. Open powershell within the extracted folder "AVALON CTF" and run `docker compose up --build`
4. Open `localhost:8080/` on your web browser

## 3. Flags for all stages:
| Stage No. | Stage Name             | Flag                                    |
| :-------: | ---------------------- | --------------------------------------- |
|     01    | Hidden in plainsight   | `AVALON{tHe_D00R_Ha$_App3ArEd}`         |
|     02    | Can’t fake permissions | `AVALON{gr3pping_a1nt_th4t_h4rd_I$_1t}` |
|     03    | The archivist’s secret | `AVALON{N0_0n3_L3ft_Th3_T0w3r}`         |
|     04    | Crack in the armor     | `AVALON{th4t_mu5t_h4v3_been_hard}`      |
|     05    | Captured login         | `AVALON{th4t_1s_why_y0u_3nabl3_http5}`  |
|     06    | Encrypted secrets      | `AVALON{w4tch_br3aking_b4d_1ts_g00d}`   |
|     07    | The unwritten trail    | `AVALON{Av4l0n_W4s_N3v3r_F0rg0tt3n}`    |

### 4. Automation/solver scripts (IT24103645 & IT24102401)

| Stage Number and Name   | Script Name             | How to Run and What It Does | Made By                        |
| ----------------------- | ----------------------- | --------------------------- | ------------------------------ |
| 04 - Crack in the armor | `crack_in_the_armor.sh` | 1. run this script with this command inside the folder that has protected zip file and the wordlist. 
2. `chmod +x crack_in_the_armor.sh`.
3. Then run this `./crack_in_the_armor.sh protected.zip wordlist-surnames-SL.txt` 

This script will extract the password hash from zip file then find the matching hash from wordlist| IT24103645 Wijayarathna P.R.M. |


### 5. Test scripts (IT24103309)
