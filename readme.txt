========================================================
PLUGIN: Zgłoszenia Shortcodes
Plik: zgloszenia-shortcodes.php
Wersja: 1.1.0
========================================================

DLA KOGO JEST TEN PLIK
----------------------
Ten dokument jest dla programisty, agenta AI lub osoby
wdrażającej zmiany. Opisuje co robi plugin, jak działa
architektura danych i jakie shortcody są dostępne.


CO TO JEST
----------
Plugin WordPress, który dostarcza shortcody do wyświetlania
danych z Custom Post Types (CPT) i pól ACF (Advanced Custom
Fields). Nie rejestruje CPT ani pól ACF — zakłada, że już
istnieją. Tylko je odczytuje i wyświetla.

Wymagane zależności:
- WordPress 5.x+
- Plugin ACF (Advanced Custom Fields) — do obsługi get_field()
- Zarejestrowane CPT i pola ACF opisane poniżej


ARCHITEKTURA DANYCH
-------------------
Dane są zorganizowane w trzech warstwach połączonych przez
relacje ACF. Ważne żeby to zrozumieć zanim cokolwiek zmienisz.

  [zgloszenie]
       |
       | pole ACF: "powiazane_osoby"
       | (lista obiektów Post Object wskazujących na łączniki)
       |
  [osoba_w_zgloszeniu]  <-- to jest ŁĄCZNIK (CPT pośredniczący)
       |                    zawiera: rolę + wskazanie na osobę
       | pole ACF: "osoba"  (Post Object)
       | pole ACF: "rola_w_sprawie"  (tekst: oprawca/ofiara/swiadek/inny)
       |
  [osoba]  <-- właściwy profil osoby
       pola ACF: imie, nazwisko, zdjecie, opis


Dlaczego łącznik a nie bezpośrednia relacja?
Bo ta sama osoba może wystąpić w wielu zgłoszeniach
w różnych rolach. Łącznik przechowuje kontekst (rolę)
dla każdego powiązania z osobna.


CPT WYMAGANE W SYSTEMIE
-----------------------
- zgloszenie          -- zdarzenie/sprawa
- osoba               -- profil osoby
- osoba_w_zgloszeniu  -- łącznik (nie wyświetlany osobom)

TAXONOMY:
- kategoria_zgloszenia -- przypisywana do CPT zgloszenie

POLA ACF (klucze używane przez plugin):

Na CPT "zgloszenie":
  data_zdarzenia      -- tekst lub data
  lokalizacja         -- tekst
  zrodlo              -- URL (wyświetlany jako link)
  powiazane_osoby     -- Post Object (many), wskazuje na osoba_w_zgloszeniu

Na CPT "osoba_w_zgloszeniu":
  osoba               -- Post Object, wskazuje na osoba
  rola_w_sprawie      -- tekst: "oprawca", "ofiara", "swiadek", "inny"
  zgloszenie          -- Post Object, wskazuje na zgloszenie (używane przy osoba_powiazania)

Na CPT "osoba":
  imie                -- tekst
  nazwisko            -- tekst
  zdjecie             -- Image (zwraca array z kluczem 'url' i 'alt')
  opis                -- textarea lub wysiwyg


SHORTCODY — PEŁNA LISTA
-----------------------

=== NA STRONIE ZGŁOSZENIA (CPT: zgloszenie) ===

Działają tylko gdy get_post_type() === 'zgloszenie'.
Odczytują dane z aktualnie oglądanego zgłoszenia.

[zgloszenie_pole pole="data_zdarzenia"]
  Wyświetla pojedyncze pole zgłoszenia.
  Dostępne wartości parametru "pole": data_zdarzenia, lokalizacja, zrodlo
  Pole "zrodlo" wyświetlane jest automatycznie jako <a href>.
  Parametry: pole, prefix, suffix

[zgloszenie_kategorie]
  Wyświetla kategorie przypisane do zgłoszenia.
  Parametry: separator (domyślnie ", "), link (tak/nie)

[zgloszenie_imie rola="oprawca"]
  Wyświetla samo imię osoby/osób o danej roli.
  Parametry: rola, index, separator, post_id

[zgloszenie_nazwisko rola="ofiara"]
  Wyświetla samo nazwisko osoby/osób o danej roli.
  Parametry: rola, index, separator, post_id

[zgloszenie_pelne_imie rola="oprawca"]
  Wyświetla "Nazwisko Imię" — jedno wywołanie, cały człowiek.
  Jeśli jest więcej osób, oddziela je separatorem (domyślnie ", ").
  Parametry: rola, index, separator, link (tak/nie), post_id
  Przykład wyjścia: "Kowalski Jan, Nowak Adam"

[zgloszenie_zdjecie rola="oprawca"]
  Wyświetla tag <img> ze zdjęciem profilowym osoby.
  Parametry: rola, index, post_id

[zgloszenie_opis rola="oprawca"]
  Wyświetla pole "opis" z profilu osoby.
  Parametry: rola, index, separator, post_id

--- Wspólne parametry dla shortcodów zgloszenie_*: ---
  rola      -- oprawca / ofiara / swiadek / inny (domyślnie: oprawca)
  index     -- numer osoby 1-based (np. index="1" = pierwsza).
               Jeśli puste, zwraca wszystkich oddzielonych separatorem.
  separator -- separator między osobami (domyślnie ", ")
  post_id   -- ID zgłoszenia, gdy używasz shortcode poza pętlą WP


=== NA DOWOLNEJ STRONIE (globalny, nie wymaga kontekstu) ===

[wyswietl_grid rola="ofiara"]
  Wyświetla responsywny grid kart wszystkich osób CPT "osoba"
  które mają powiązanie w łączniku z daną rolą.
  Używaj np. na stronie /ofiary, /oprawcy itp.
  Każda karta zawiera: zdjęcie (lub placeholder z literą) + Nazwisko Imię.
  Karta jest linkiem do profilu osoby (można wyłączyć).

  Parametry:
    rola     -- oprawca / ofiara / swiadek / inny (puste = wszyscy)
    kolumny  -- liczba kolumn na desktopie, 1-6 (domyślnie 3)
    link     -- tak/nie (domyślnie tak)
    limit    -- max liczba osób (-1 = wszyscy, domyślnie -1)

  Responsywność:
    Desktop (>900px)  -- tyle kolumn ile w parametrze "kolumny"
    Tablet (<=900px)  -- max 2 kolumny
    Mobile (<=540px)  -- 1 kolumna, karta pozioma (zdjęcie + tekst obok)


=== NA STRONIE PROFILU OSOBY (CPT: osoba) ===

Działają tylko gdy get_post_type() === 'osoba'.

[osoba_pole pole="imie"]
  Wyświetla pojedyncze pole ACF z profilu osoby.
  Dostępne wartości: imie, nazwisko, opis, zdjecie
  Pole "zdjecie" wyświetla <img class="zs-osoba-zdjecie">.
  Pole "opis" wyświetla <div class="zs-osoba-opis">.
  Parametry: pole, prefix, suffix, rozmiar

[osoba_powiazania]
  Wyświetla listę wszystkich zgłoszeń powiązanych z tą osobą.
  Format każdej pozycji: Tytuł zgłoszenia — Rola
  Rola wyświetlana jako kolorowy badge.
  Parametry: link (tak/nie, domyślnie tak)
  Przykład wyjścia:
    • Zdarzenie w Krakowie — Ofiara
    • Zdarzenie w Gdańsku — Świadek

[osoba_zgloszenia rola="oprawca" limit="10"]
  Starsza wersja — wyświetla listę <ul> zgłoszeń osoby,
  opcjonalnie filtrując po roli.
  Różnica vs osoba_powiazania: tu możesz filtrować po roli
  i ustawić limit. osoba_powiazania pokazuje zawsze wszystkie.
  Parametry: rola, limit, format


CSS I STYLOWANIE
----------------
Wszystkie style są wstrzykiwane przez wp_head (hook WordPress).
Ładowane są raz na stronę (flaga static $juz_zaladowane).
Nie ma osobnego pliku .css — wszystko jest w pliku PHP.

Klasy CSS w systemie:
  .zs-grid               -- kontener grida
  .zs-grid-karta         -- pojedyncza karta
  .zs-grid-karta--link   -- karta będąca linkiem (hover efekt)
  .zs-grid-zdjecie       -- zdjęcie w karcie (80x80, okrąg)
  .zs-grid-placeholder   -- okrąg z literą gdy brak zdjęcia
  .zs-grid-info          -- kontener na tekst w karcie
  .zs-grid-imie          -- imię/nazwisko w karcie
  .zs-grid-rola          -- badge roli (pill)
  .zs-rola--oprawca      -- czerwony badge
  .zs-rola--ofiara       -- niebieski badge
  .zs-rola--swiadek      -- zielony badge
  .zs-rola--inny         -- szary badge
  .zs-powiazania-lista   -- lista <ul> z osoba_powiazania
  .zs-powiazania-pozycja -- pojedyncza pozycja listy
  .zs-osoby-lista        -- lista z zgloszenie_osoby
  .zs-osoba-zdjecie      -- zdjęcie z osoba_pole
  .zs-osoba-opis         -- opis z osoba_pole


FUNKCJA POMOCNICZA (wewnętrzna)
-------------------------------
get_person_field_by_role( $atts, $field_name )

Używana wewnętrznie przez: zgloszenie_imie, zgloszenie_nazwisko,
zgloszenie_zdjecie, zgloszenie_opis.

Przechodzi przez powiazane_osoby danego zgłoszenia,
filtruje po roli, zbiera wartości pola field_name z profilu osoby.
Obsługuje parametry: rola, index, separator, post_id.

Shortcode zgloszenie_pelne_imie ma własną logikę (nie używa tej
funkcji) bo skleja dwa pola naraz — imie + nazwisko.


STRONA USTAWIEŃ W ADMINIE
--------------------------
Plugin dodaje stronę Ustawienia > Zgłoszenia Shortcodes.
Zawiera tabelaryczną dokumentację wszystkich shortcodów
wraz z parametrami i przykładami. Bez żadnych opcji do
zapisywania — czysto informacyjna.


CZEGO PLUGIN NIE ROBI (co może być potrzebne w przyszłości)
------------------------------------------------------------
- Nie rejestruje CPT ani taxonomii — zakłada że istnieją
- Nie rejestruje pól ACF — zakłada że istnieją
- Brak paginacji w [wyswietl_grid] — przy dużej liczbie osób
  trzeba będzie dodać parametr "offset" i przyciski Poprzednia/Następna
- Brak sortowania w gridzie — osoby pokazują się w kolejności
  w jakiej WP_Query je zwróci (domyślnie po dacie dodania)
- Brak cachowania zapytań — przy dużej bazie danych warto
  owinąć get_posts() w wp_cache_get/wp_cache_set
- CSS hardcoded w PHP — jeśli motyw nadpisuje style,
  trzeba dodać !important lub wynieść CSS do osobnego pliku
- Brak obsługi AJAX / infinite scroll dla grida


TYPOWE BŁĘDY I DEBUG
---------------------
Shortcode zwraca pusty string ("") gdy:
  1. get_post_type() nie zgadza się z oczekiwanym CPT
     -> sprawdź czy jesteś na właściwej stronie
  2. get_field() zwraca null/false
     -> sprawdź nazwy pól w ACF (uwaga na spacje, wielkość liter)
  3. Pole "powiazane_osoby" jest puste lub nie wskazuje na łączniki
     -> sprawdź w ACF czy relacja jest poprawnie skonfigurowana
  4. Łącznik nie ma wypełnionego pola "osoba"
     -> dane niekompletne w bazie

Jak testować: wklej shortcode w treść strony lub użyj
echo do_shortcode('[nazwa_shortcode]') w pliku PHP motywu.


HISTORIA ZMIAN
--------------
v1.0.0 -- Wersja początkowa
  Shortcody: zgloszenie_pole, zgloszenie_kategorie,
  zgloszenie_osoby, osoba_pole, osoba_zgloszenia,
  zgloszenie_imie, zgloszenie_nazwisko, zgloszenie_zdjecie,
  zgloszenie_opis

v1.1.0 -- Dodano
  [zgloszenie_pelne_imie] -- Nazwisko Imię jednym wywołaniem
  [wyswietl_grid]         -- Globalny grid osób po roli (przepisany)
  [osoba_powiazania]      -- Lista zgłoszeń na profilu osoby
  CSS responsywny dla grida (breakpointy: 900px, 540px)
