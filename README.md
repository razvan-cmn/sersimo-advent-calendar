# Sersimo – Calendar de Advent

Plugin WordPress/WooCommerce pentru sersimo.ro: popup cu 24 de uși (1–24 decembrie). Ușa zilei e aprinsă, iar la click cuponul apare în același popup.

- **Demo online:** https://razvan-cmn.github.io/sersimo-advent-calendar/demo/ (din caseta din dreapta-sus alegi ziua simulată)
- **Plugin:** folderul [`sersimo-advent-calendar/`](sersimo-advent-calendar/). Zip-ul gata de încărcat în WordPress e în [Releases](https://github.com/razvan-cmn/sersimo-advent-calendar/releases).
- **Instrucțiuni de instalare și setări:** [`sersimo-advent-calendar/README.md`](sersimo-advent-calendar/README.md)

## Pe scurt

- În pagină stă doar un loader inline de ~2,3 KB. Calendarul (JS + CSS, ~6,6 KB comprimat) se încarcă abia la deschidere.
- Datele vin din `/wp-json/sersimo-advent/v1/zile`: ziua de azi completă, zilele trecute doar cu emoji, nimic din viitor.
- Cupoanele se creează în WooCommerce. Pluginul doar le afișează. Codurile din demo (`ADVENT01`…`ADVENT24`) sunt exemple.

## Structură

| Cale | Ce e |
|---|---|
| `sersimo-advent-calendar/` | pluginul (PHP + `assets/`) |
| `demo/` | pagină de test fără WordPress (`?zi=N`, `?auto=0`, `?api=1`) |
| `tests/advent-harness.php` | teste PHP pe un WordPress simulat: `php tests/advent-harness.php sersimo-advent-calendar` |

Local: `python -m http.server 8791`, apoi http://localhost:8791/demo/
