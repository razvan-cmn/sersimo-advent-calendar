# Sersimo – Calendar de Advent (v1.1.0)

Popup roz (culoarea brandului) cu 24 de uși verzi (1–24 decembrie). Ușa zilei e roșie și strălucește. La click se deschide, iar în același popup apare cuponul: reducerea într-un glob sau cadou, produsul redus (poză, nume complet, preț), codul cu buton „Copiază” și link spre produs.

După ce omul a deschis ușa zilei, butonul flotant se schimbă în „🎁 -20% · Brad Global nins…” și duce **direct la cupon** până la miezul nopții. Dacă închide popup-ul cu X, butonul pulsează scurt, ca să vadă unde a rămas oferta.

## Instalare

1. WordPress → Module → Adaugă → Încarcă `sersimo-advent-calendar.zip` → Activează.
2. **WooCommerce → Calendar Advent**.
3. Completați cele 24 de zile și salvați. Calendarul e **oprit** până bifați „Activ pe site”.

## Cupoanele

Pluginul **nu creează și nu modifică cupoane**. Le faceți voi în WooCommerce → Marketing → Cupoane (reducere, brazii la care se aplică, expirare). În pagina pluginului puneți doar ce se afișează:

| Câmp | Exemplu | Observații |
|---|---|---|
| Emoji, formă, culoare | 🎄, glob, roșu | Forma: glob sau cadou. Culoarea e a globului sau a cadoului; evitați roz, popup-ul e roz |
| Text mare | `-20%` | Apare în glob |
| Produs redus | caută „Global nins 210” | Poza, numele, prețul și linkul se iau automat din WooCommerce |
| Nume afișat | `Brad Global nins 2D+3D, 210 cm` | Opțional. Gol = numele produsului fără „Brad artificial Christmas Deluxe by Sersimo,” |
| Preț cu codul | `560.00 lei` | Opțional. Apare cu roșu, iar prețul actual apare tăiat. Gol = doar prețul actual (și cel vechi, dacă produsul e deja la reducere) |
| Descriere, cod, text buton | | |
| Link buton | | Opțional. Gol = pagina produsului |

- Lângă fiecare cod apare dacă există sau nu un cupon cu acel cod în WooCommerce (doar verificare).
- WooCommerce nu are „valabil de la”: pentru un cupon valabil o singură zi, puneți expirarea în ziua respectivă și **nu îl publicați înainte** (sau programați publicarea). Calendarul arată codul doar din ziua lui, dar cuponul în sine merge atâta timp cât e publicat.

## Previzualizare (doar pentru admin)

Din pagina pluginului: „Previzualizează ziua N pe site”. Merge și cu calendarul oprit: adăugați `?advent_zi=N#calendar-advent` la orice URL al site-ului cât timp sunteți logați ca admin sau shop manager. Vizitatorii nu pot folosi parametrul.

Ca să vedeți din nou deschiderea automată sau punctul de pe buton, ștergeți din browser datele site-ului (cheile `ssadv_open`, `ssadv_auto` și `ssadv_cupon` din localStorage).

## Cum funcționează (consum minim)

- **Pe pagină stă doar un script inline de ~2 KB** (butonul flotant). Fără jQuery, imagini, fonturi noi sau biblioteci; folosește fontul Oswald pe care site-ul îl are deja.
- `assets/advent.js` + `assets/advent.css` (~6,5 KB comprimat) și datele se încarcă **abia la deschidere**.
- Datele vin din `GET /wp-json/sersimo-advent/v1/zile`: **ziua de azi completă, zilele trecute doar cu emoji, nimic din viitor**, deci codurile nu pot fi aflate dinainte. Ziua se calculează pe server, după fusul orar al site-ului (Europe/Bucharest).
- Răspunsul REST are cache public de maximum 5 minute, care nu trece niciodată de miezul nopții. Același TTL e trimis și către LiteSpeed.
- Paginile rămân în cache (LiteSpeed/Cloudflare): în HTML nu e nimic legat de zi.
- La salvare se golește cache-ul LiteSpeed și crește versiunea fișierelor JS/CSS.
- Scriptul are `data-no-optimize="1"` și `data-cfasync="false"`, ca să nu fie combinat sau amânat.

## Setări

| Setare | Implicit | Ce face |
|---|---|---|
| Activ pe site | oprit | Arată calendarul vizitatorilor |
| Anul | anul curent | Ușile 1–24 decembrie ale acestui an |
| Deschidere automată | 8 s | O dată pe zi, doar dacă ușa zilei nu e deschisă și bannerul de cookies e închis. 0 = doar la click |
| Teaser | oprit | Butonul apare și înainte de 1 decembrie (uși încuiate, „mai sunt X zile”) |
| Coș / checkout | ascuns | Fără buton în coș, checkout și contul meu |
| Poziție buton | stânga-jos | Sau dreapta-jos (atenție la butonul WhatsApp) |
| Banner cookies | `#cookie-law-info-bar` | Selectorul bannerului CookieYes |

## Link direct

`https://sersimo.ro/#calendar-advent` deschide calendarul (pentru newsletter, reclame, story-uri).

## Măsurare (dataLayer / GTM)

| Eveniment | Când | Parametri |
|---|---|---|
| `advent_deschis` | se deschide popup-ul | `sursa`: buton / auto / link |
| `advent_usa_deschisa` | ușa zilei | `usa`, `prima_data` |
| `advent_usa_incuiata` | click pe o ușă viitoare | `usa` |
| `advent_usa_expirata` | click pe o ușă trecută | `usa` |
| `advent_cod_copiat` | „Copiază” | `advent_zi` |
| `advent_click_oferta` | butonul ofertei | `usa`, `link` |

Toate au și `advent_zi`. Comenzile cu cupon se văd direct în WooCommerce (coloana cupoane / rapoarte).

## Dezinstalare

Ștergerea pluginului șterge setările lui (`ssadv_settings`, `ssadv_days`, `ssadv_rev`). Cupoanele WooCommerce rămân neatinse.
