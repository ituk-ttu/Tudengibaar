# Tudengibaar

Tudengibaar on veebipõhine rakendus, mis on mõeldud jookide hindade haldamiseks ja jälgimiseks baarikeskkonnas. See integreerib dünaamilise hinnastamissüsteemi, kus hindu kohandatakse müügi põhjal ja kasutaja tegevuse järgi. Projekt on üles ehitatud PHP, MySQL ja Highchartsi abil, et kuvada dünaamilisi graafikuid hindade ajaloo ja prognooside kohta.

## Funktsioonid

- **Baartöötaja kassa**: Loob baaritöötajatele UI, kus kinnitatakse tellimus ning muudetakse hindu
- **Dünaamiline jookide hindamine**: Iga joogi hind kohandatakse automaatselt müügistatistika ja hinnareeglite põhjal.
- **Reaalajas hinna graafikud**: Süsteem pakub iga joogi jaoks reaalajas hinna graafikuid, kasutades Highchartsi, koos ajapõhiste prognoosidega.
- **Hinna ajalugu ja prognoosimine**: Kuvatakse hinna ajalugu koos reaalajas ja tulevaste hinna prognoosidega iga joogi kohta.
- **Admin Paneel**: Võimaldab manuaalselt hindu kas muuta või määrata, et jook on välja müüdud

## Requirements

- Composer 2.8.3
- PHP 8.3.13

## Deployment

```bash
  composer install
```
