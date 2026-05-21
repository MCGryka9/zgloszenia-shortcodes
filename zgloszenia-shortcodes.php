<?php
/**
 * Plugin Name: Zgłoszenia Shortcodes
 * Plugin URI: Gryczan.eu
 * Description: Shortcody do wyświetlania danych zgłoszeń i osób (ACF + CPT)
 * Version: 1.2.0
 * Author: Gryczan.eu
 *
 * ARCHITEKTURA DANYCH:
 *   [zgloszenie] → powiazane_osoby → [osoba_w_zgloszeniu] → osoba → [osoba]
 *   Łącznik osoba_w_zgloszeniu przechowuje kontekst: rola_w_sprawie
 *
 * WYMAGANE CPT:   zgloszenie, osoba, osoba_w_zgloszeniu
 * WYMAGANE POLA ACF:
 *   zgloszenie:          data_zdarzenia, lokalizacja, zrodlo, powiazane_osoby
 *   osoba_w_zgloszeniu:  osoba (Post Object), rola_w_sprawie, zgloszenie (Post Object)
 *   osoba:               imie, nazwisko, zdjecie, opis
 */

if ( ! defined( 'ABSPATH' ) ) exit;


/* ============================================================
   SEKCJA 1: SHORTCODY NA STRONIE ZGŁOSZENIA
   Działają tylko na CPT "zgloszenie"
   ============================================================ */


/**
 * Wyświetla pojedyncze pole ACF z aktualnego zgłoszenia.
 * Pole "zrodlo" renderowane jest automatycznie jako <a href>.
 *
 * Użycie: [zgloszenie_pole pole="data_zdarzenia"]
 * Parametry: pole (data_zdarzenia|lokalizacja|zrodlo), prefix, suffix
 */
add_shortcode( 'zgloszenie_pole', function( $atts ) {

    if ( get_post_type() !== 'zgloszenie' ) return '';

    $atts = shortcode_atts( [
        'pole'   => '',
        'prefix' => '',
        'suffix' => '',
    ], $atts );

    $dozwolone = [ 'data_zdarzenia', 'lokalizacja', 'zrodlo' ];
    if ( ! in_array( $atts['pole'], $dozwolone ) ) return '';

    $wartosc = get_field( $atts['pole'] );
    if ( ! $wartosc ) return '';

    if ( $atts['pole'] === 'zrodlo' ) {
        return '<a href="' . esc_url( $wartosc ) . '" target="_blank" rel="noopener">'
            . esc_html( $wartosc ) . '</a>';
    }

    return esc_html( $atts['prefix'] ) . esc_html( $wartosc ) . esc_html( $atts['suffix'] );
} );


/**
 * Wyświetla kategorie przypisane do zgłoszenia.
 *
 * Użycie: [zgloszenie_kategorie]
 * Parametry: separator (domyślnie ", "), link (tak|nie)
 */
add_shortcode( 'zgloszenie_kategorie', function( $atts ) {

    if ( get_post_type() !== 'zgloszenie' ) return '';

    $atts = shortcode_atts( [
        'separator' => ', ',
        'link'      => 'tak',
    ], $atts );

    $terminy = get_the_terms( get_the_ID(), 'kategoria_zgloszenia' );
    if ( ! $terminy || is_wp_error( $terminy ) ) return '';

    $lista = [];
    foreach ( $terminy as $termin ) {
        $nazwa = esc_html( $termin->name );
        $lista[] = $atts['link'] === 'tak'
            ? '<a href="' . esc_url( get_term_link( $termin ) ) . '">' . $nazwa . '</a>'
            : $nazwa;
    }

    return implode( esc_html( $atts['separator'] ), $lista );
} );


/**
 * Wyświetla listę osób powiązanych ze zgłoszeniem jako <ul> lub inline.
 * Opcjonalnie filtruje po roli, pokazuje zdjęcia, linki do profili.
 *
 * Użycie: [zgloszenie_osoby rola="oprawca"]
 * Parametry: rola, format (lista|inline), pokaz_role (tak|nie),
 *            pokaz_zdjecie (tak|nie), link (tak|nie)
 */
add_shortcode( 'zgloszenie_osoby', function( $atts ) {

    if ( get_post_type() !== 'zgloszenie' ) return '';

    $atts = shortcode_atts( [
        'rola'          => '',
        'format'        => 'lista',
        'pokaz_role'    => 'tak',
        'pokaz_zdjecie' => 'nie',
        'link'          => 'tak',
    ], $atts );

    $powiazane = get_field( 'powiazane_osoby' );
    if ( ! $powiazane ) return '';

    $etykiety = [ 'oprawca' => 'Oprawca', 'ofiara' => 'Ofiara', 'swiadek' => 'Świadek', 'inny' => 'Inny' ];
    $wyniki   = [];

    foreach ( $powiazane as $lacze ) {

        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze->ID );
        $osoba_post     = get_field( 'osoba', $lacze->ID );

        if ( ! $osoba_post ) continue;
        if ( $atts['rola'] !== '' && $rola_w_sprawie !== $atts['rola'] ) continue;

        $imie     = get_field( 'imie', $osoba_post->ID );
        $nazwisko = get_field( 'nazwisko', $osoba_post->ID );
        $zdjecie  = get_field( 'zdjecie', $osoba_post->ID );
        $pelne    = esc_html( trim( $imie . ' ' . $nazwisko ) );

        $nazwa_html = $atts['link'] === 'tak'
            ? '<a href="' . esc_url( get_permalink( $osoba_post->ID ) ) . '">' . $pelne . '</a>'
            : $pelne;

        $zdjecie_html = '';
        if ( $atts['pokaz_zdjecie'] === 'tak' && $zdjecie ) {
            $url          = is_array( $zdjecie ) ? $zdjecie['url'] : $zdjecie;
            $zdjecie_html = '<img src="' . esc_url( $url ) . '" alt="' . $pelne
                . '" style="width:50px;height:50px;object-fit:cover;border-radius:50%;margin-right:8px;vertical-align:middle;" />';
        }

        $rola_html = '';
        if ( $atts['pokaz_role'] === 'tak' ) {
            $etykieta  = isset( $etykiety[ $rola_w_sprawie ] ) ? $etykiety[ $rola_w_sprawie ] : $rola_w_sprawie;
            $rola_html = ' <span class="zs-rola zs-rola--' . esc_attr( $rola_w_sprawie ) . '">'
                . esc_html( $etykieta ) . '</span>';
        }

        $wyniki[] = $zdjecie_html . $nazwa_html . $rola_html;
    }

    if ( empty( $wyniki ) ) return '';

    if ( $atts['format'] === 'inline' ) {
        return '<span class="zs-osoby-inline">' . implode( ', ', $wyniki ) . '</span>';
    }

    return '<ul class="zs-osoby-lista"><li class="zs-osoba">'
        . implode( '</li><li class="zs-osoba">', $wyniki )
        . '</li></ul>';
} );


/* ============================================================
   SEKCJA 2: FUNKCJA POMOCNICZA + SHORTCODY PÓL OSOBY
   Pobierają jedno pole z osób o danej roli w zgłoszeniu
   ============================================================ */


/**
 * Funkcja pomocnicza — pobiera wartość pola ACF z osób powiązanych
 * ze zgłoszeniem, filtrując po roli.
 * Używana przez: zgloszenie_imie, zgloszenie_nazwisko,
 *                zgloszenie_zdjecie, zgloszenie_opis
 *
 * @param array  $atts       Parametry shortcode
 * @param string $field_name Nazwa pola ACF do pobrania
 * @return string            Gotowy HTML lub tekst
 */
function zs_get_person_field_by_role( $atts, $field_name = 'imie' ) {

    $a = shortcode_atts( [
        'rola'      => 'oprawca',
        'index'     => null,
        'post_id'   => get_the_ID(),
        'separator' => ', ',
    ], $atts );

    if ( ! $a['post_id'] ) return '';

    $powiazane = get_field( 'powiazane_osoby', $a['post_id'] );
    if ( ! $powiazane || ! is_array( $powiazane ) ) return '';

    $wartosci = [];

    foreach ( $powiazane as $lacze ) {

        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze->ID );
        if ( $rola_w_sprawie !== $a['rola'] ) continue;

        $osoba_post = get_field( 'osoba', $lacze->ID );
        if ( ! $osoba_post ) continue;

        $wartosc = get_field( $field_name, $osoba_post->ID );
        if ( ! $wartosc ) continue;

        if ( $field_name === 'zdjecie' ) {
            $url        = is_array( $wartosc ) ? $wartosc['url'] : $wartosc;
            $wartosci[] = '<img src="' . esc_url( $url ) . '" class="zs-foto-' . esc_attr( $a['rola'] ) . '">';
        } else {
            $wartosci[] = esc_html( $wartosc );
        }
    }

    if ( empty( $wartosci ) ) return '';

    if ( $a['index'] !== null ) {
        $idx = (int) $a['index'] - 1;
        return $wartosci[ $idx ] ?? '';
    }

    return implode( $a['separator'], $wartosci );
}


/**
 * Wyświetla imię osoby/osób o danej roli w zgłoszeniu.
 * Użycie: [zgloszenie_imie rola="oprawca" index="1"]
 */
add_shortcode( 'zgloszenie_imie', function( $atts ) {
    return zs_get_person_field_by_role( $atts, 'imie' );
} );


/**
 * Wyświetla nazwisko osoby/osób o danej roli w zgłoszeniu.
 * Użycie: [zgloszenie_nazwisko rola="ofiara"]
 */
add_shortcode( 'zgloszenie_nazwisko', function( $atts ) {
    return zs_get_person_field_by_role( $atts, 'nazwisko' );
} );


/**
 * Wyświetla tag <img> ze zdjęciem osoby o danej roli.
 * Użycie: [zgloszenie_zdjecie rola="oprawca"]
 */
add_shortcode( 'zgloszenie_zdjecie', function( $atts ) {
    return zs_get_person_field_by_role( $atts, 'zdjecie' );
} );


/**
 * Wyświetla pole "opis" osoby o danej roli.
 * Użycie: [zgloszenie_opis rola="oprawca"]
 */
add_shortcode( 'zgloszenie_opis', function( $atts ) {
    return zs_get_person_field_by_role( $atts, 'opis' );
} );


/**
 * Wyświetla "Nazwisko Imię" — oba pola jednym wywołaniem.
 * Jeśli więcej osób o danej roli, oddziela je separatorem.
 *
 * Użycie: [zgloszenie_pelne_imie rola="oprawca"]
 * Parametry: rola, index, separator (domyślnie ", "), link (tak|nie), post_id
 */
add_shortcode( 'zgloszenie_pelne_imie', function( $atts ) {

    $atts = shortcode_atts( [
        'rola'      => 'oprawca',
        'index'     => null,
        'post_id'   => get_the_ID(),
        'separator' => ', ',
        'link'      => 'tak',
    ], $atts );

    if ( ! $atts['post_id'] ) return '';

    $powiazane = get_field( 'powiazane_osoby', $atts['post_id'] );
    if ( ! $powiazane || ! is_array( $powiazane ) ) return '';

    $wyniki = [];

    foreach ( $powiazane as $lacze ) {

        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze->ID );
        if ( $rola_w_sprawie !== $atts['rola'] ) continue;

        $osoba_post = get_field( 'osoba', $lacze->ID );
        if ( ! $osoba_post ) continue;

        $imie     = get_field( 'imie', $osoba_post->ID );
        $nazwisko = get_field( 'nazwisko', $osoba_post->ID );
        if ( ! $imie && ! $nazwisko ) continue;

        $pelne = trim( esc_html( $nazwisko ) . ' ' . esc_html( $imie ) );

        $wyniki[] = $atts['link'] === 'tak'
            ? '<a href="' . esc_url( get_permalink( $osoba_post->ID ) ) . '">' . $pelne . '</a>'
            : $pelne;
    }

    if ( empty( $wyniki ) ) return '';

    if ( $atts['index'] !== null ) {
        $idx = (int) $atts['index'] - 1;
        return $wyniki[ $idx ] ?? '';
    }

    return implode( esc_html( $atts['separator'] ), $wyniki );
} );


/* ============================================================
   SEKCJA 3: SHORTCODY NA STRONIE PROFILU OSOBY
   Działają na CPT "osoba"
   ============================================================ */


/**
 * Wyświetla pojedyncze pole ACF z profilu osoby.
 * Pole "zdjecie" renderuje <img>, pole "opis" renderuje <div>.
 *
 * Użycie: [osoba_pole pole="imie"]
 * Parametry: pole (imie|nazwisko|opis|zdjecie), prefix, suffix
 */
add_shortcode( 'osoba_pole', function( $atts ) {

    if ( get_post_type() !== 'osoba' ) return '';

    $atts = shortcode_atts( [
        'pole'   => '',
        'prefix' => '',
        'suffix' => '',
    ], $atts );

    $dozwolone = [ 'imie', 'nazwisko', 'opis', 'zdjecie' ];
    if ( ! in_array( $atts['pole'], $dozwolone ) ) return '';

    $wartosc = get_field( $atts['pole'] );
    if ( ! $wartosc ) return '';

    if ( $atts['pole'] === 'zdjecie' ) {
        $url = is_array( $wartosc ) ? $wartosc['url'] : $wartosc;
        $alt = is_array( $wartosc ) ? $wartosc['alt'] : '';
        return '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" class="zs-osoba-zdjecie" />';
    }

    if ( $atts['pole'] === 'opis' ) {
        return '<div class="zs-osoba-opis">' . wp_kses_post( $wartosc ) . '</div>';
    }

    return esc_html( $atts['prefix'] ) . esc_html( $wartosc ) . esc_html( $atts['suffix'] );
} );


/**
 * Na profilu osoby wyświetla listę zgłoszeń w których ta osoba uczestniczyła
 * wraz z jej rolą w każdej sprawie. Format: • Tytuł zgłoszenia — Rola
 *
 * Użycie: [osoba_powiazania]
 * Parametry: link (tak|nie)
 */
add_shortcode( 'osoba_powiazania', function( $atts ) {

    if ( get_post_type() !== 'osoba' ) return '';

    $atts = shortcode_atts( [ 'link' => 'tak' ], $atts );

    $etykiety = [ 'oprawca' => 'Oprawca', 'ofiara' => 'Ofiara', 'swiadek' => 'Świadek', 'inny' => 'Inny' ];

    $lacze_ids = get_posts( [
        'post_type'      => 'osoba_w_zgloszeniu',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [ [ 'key' => 'osoba', 'value' => get_the_ID(), 'compare' => '=' ] ],
    ] );

    if ( empty( $lacze_ids ) ) return '';

    $pozycje = [];

    foreach ( $lacze_ids as $lacze_id ) {

        $zgloszenie     = get_field( 'zgloszenie', $lacze_id );
        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze_id );

        if ( ! $zgloszenie ) continue;

        $tytul    = get_the_title( $zgloszenie->ID );
        $etykieta = isset( $etykiety[ $rola_w_sprawie ] ) ? $etykiety[ $rola_w_sprawie ] : esc_html( $rola_w_sprawie );

        $tytul_html = $atts['link'] === 'tak'
            ? '<a href="' . esc_url( get_permalink( $zgloszenie->ID ) ) . '">' . esc_html( $tytul ) . '</a>'
            : esc_html( $tytul );

        $pozycje[] = $tytul_html
            . ' — <span class="zs-rola zs-rola--' . esc_attr( $rola_w_sprawie ) . '">'
            . esc_html( $etykieta ) . '</span>';
    }

    if ( empty( $pozycje ) ) return '';

    return '<ul class="zs-powiazania-lista"><li class="zs-powiazania-pozycja">'
        . implode( '</li><li class="zs-powiazania-pozycja">', $pozycje )
        . '</li></ul>';
} );


/**
 * Wyświetla pełny profil osoby: zdjęcie, imię i nazwisko (H1),
 * a pod nim listę wszystkich powiązanych zgłoszeń z rolą.
 * Używaj jako główny shortcode na template strony osoby.
 *
 * Użycie: [osoba_profil]
 * Parametry: link (tak|nie) — czy tytuły zgłoszeń są linkami
 */
add_shortcode( 'osoba_profil', function( $atts ) {

    if ( get_post_type() !== 'osoba' ) return '';

    $atts = shortcode_atts( [ 'link' => 'tak' ], $atts );

    $osoba_id = get_the_ID();
    $imie     = get_field( 'imie', $osoba_id );
    $nazwisko = get_field( 'nazwisko', $osoba_id );
    $zdjecie  = get_field( 'zdjecie', $osoba_id );
    $etykiety = [ 'oprawca' => 'Oprawca', 'ofiara' => 'Ofiara', 'swiadek' => 'Świadek', 'inny' => 'Inny' ];

    $pelne_imie = trim( esc_html( $nazwisko ) . ' ' . esc_html( $imie ) );

    // Zdjęcie
    $zdjecie_html = '';
    if ( $zdjecie ) {
        $url          = is_array( $zdjecie ) ? $zdjecie['url'] : $zdjecie;
        $alt          = is_array( $zdjecie ) ? $zdjecie['alt'] : $pelne_imie;
        $zdjecie_html = '<img src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" class="zs-profil-zdjecie" />';
    } else {
        $litera       = esc_html( mb_substr( $nazwisko ?: $imie, 0, 1 ) );
        $zdjecie_html = '<div class="zs-profil-placeholder" aria-hidden="true"><span>' . $litera . '</span></div>';
    }

    // Lista zgłoszeń
    $lacze_ids = get_posts( [
        'post_type'      => 'osoba_w_zgloszeniu',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [ [ 'key' => 'osoba', 'value' => $osoba_id, 'compare' => '=' ] ],
    ] );

    $lista_html = '';

    if ( ! empty( $lacze_ids ) ) {

        $pozycje = [];

        foreach ( $lacze_ids as $lacze_id ) {

            $zgloszenie     = get_field( 'zgloszenie', $lacze_id );
            $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze_id );

            if ( ! $zgloszenie ) continue;

            $tytul    = get_the_title( $zgloszenie->ID );
            $etykieta = isset( $etykiety[ $rola_w_sprawie ] ) ? $etykiety[ $rola_w_sprawie ] : esc_html( $rola_w_sprawie );

            $tytul_html = $atts['link'] === 'tak'
                ? '<a href="' . esc_url( get_permalink( $zgloszenie->ID ) ) . '">' . esc_html( $tytul ) . '</a>'
                : esc_html( $tytul );

            $pozycje[] = '<li class="zs-profil-sprawa">'
                . $tytul_html
                . ' <span class="zs-rola zs-rola--' . esc_attr( $rola_w_sprawie ) . '">'
                . esc_html( $etykieta ) . '</span>'
                . '</li>';
        }

        if ( ! empty( $pozycje ) ) {
            $lista_html = '<h2 class="zs-profil-sprawy-tytul">Powiązane sprawy</h2>'
                . '<ul class="zs-profil-sprawy-lista">' . implode( '', $pozycje ) . '</ul>';
        }
    }

    return '<div class="zs-profil">'
        . $zdjecie_html
        . '<div class="zs-profil-dane">'
        . '<h1 class="zs-profil-imie">' . $pelne_imie . '</h1>'
        . $lista_html
        . '</div>'
        . '</div>';
} );


/**
 * Filtrowana lista zgłoszeń osoby — prostsza wersja, tylko lista <ul>.
 * Przydatna gdy chcesz pokazać tylko zgłoszenia z daną rolą.
 *
 * Użycie: [osoba_zgloszenia rola="oprawca" limit="10"]
 * Parametry: rola, limit (domyślnie 10)
 */
add_shortcode( 'osoba_zgloszenia', function( $atts ) {

    if ( get_post_type() !== 'osoba' ) return '';

    $atts     = shortcode_atts( [ 'rola' => '', 'limit' => 10 ], $atts );
    $etykiety = [ 'oprawca' => 'Oprawca', 'ofiara' => 'Ofiara', 'swiadek' => 'Świadek', 'inny' => 'Inny' ];

    $meta_query = [ [ 'key' => 'osoba', 'value' => get_the_ID(), 'compare' => '=' ] ];
    if ( $atts['rola'] !== '' ) {
        $meta_query[] = [ 'key' => 'rola_w_sprawie', 'value' => $atts['rola'], 'compare' => '=' ];
    }

    $lacze_ids = get_posts( [
        'post_type'      => 'osoba_w_zgloszeniu',
        'posts_per_page' => (int) $atts['limit'],
        'fields'         => 'ids',
        'meta_query'     => $meta_query,
    ] );

    if ( empty( $lacze_ids ) ) return '';

    $pozycje = [];

    foreach ( $lacze_ids as $lacze_id ) {

        $zgloszenie     = get_field( 'zgloszenie', $lacze_id );
        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze_id );

        if ( ! $zgloszenie ) continue;

        $etykieta = isset( $etykiety[ $rola_w_sprawie ] ) ? $etykiety[ $rola_w_sprawie ] : esc_html( $rola_w_sprawie );

        $pozycje[] = '<li><a href="' . esc_url( get_permalink( $zgloszenie->ID ) ) . '">'
            . esc_html( get_the_title( $zgloszenie->ID ) ) . '</a>'
            . ' <span class="zs-rola zs-rola--' . esc_attr( $rola_w_sprawie ) . '">'
            . esc_html( $etykieta ) . '</span></li>';
    }

    if ( empty( $pozycje ) ) return '';

    return '<ul class="zs-zgloszenia-lista">' . implode( '', $pozycje ) . '</ul>';
} );


/* ============================================================
   SEKCJA 4: GLOBALNY GRID OSÓB
   Działa na dowolnej stronie, nie wymaga kontekstu zgłoszenia
   ============================================================ */


/**
 * Wyświetla responsywny grid kart wszystkich osób CPT "osoba"
 * które mają powiązanie w łączniku z daną rolą.
 * Typowe użycie: strona /ofiary, /oprawcy, /swiadkowie
 *
 * Karta zawiera: zdjęcie (lub placeholder z literą) + Nazwisko Imię
 *
 * Użycie: [wyswietl_grid rola="ofiara"]
 * Parametry: rola (oprawca|ofiara|swiadek|inny, puste=wszyscy),
 *            kolumny (1-6, domyślnie 3), link (tak|nie), limit (-1=wszyscy)
 */
add_shortcode( 'wyswietl_grid', function( $atts ) {

    $atts = shortcode_atts( [
        'rola'    => '',
        'kolumny' => '3',
        'link'    => 'tak',
        'limit'   => -1,
    ], $atts );

    // Pobierz łączniki z opcjonalnym filtrem roli
    $lacze_args = [
        'post_type'      => 'osoba_w_zgloszeniu',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    if ( $atts['rola'] !== '' ) {
        $lacze_args['meta_query'] = [
            [ 'key' => 'rola_w_sprawie', 'value' => $atts['rola'], 'compare' => '=' ],
        ];
    }

    $lacze_ids = get_posts( $lacze_args );
    if ( empty( $lacze_ids ) ) return '';

    // Zbierz unikalne ID osób (CPT osoba)
    $osoby_ids = [];
    foreach ( $lacze_ids as $lacze_id ) {
        $osoba_post = get_field( 'osoba', $lacze_id );
        if ( $osoba_post && ! in_array( $osoba_post->ID, $osoby_ids ) ) {
            $osoby_ids[] = $osoba_post->ID;
        }
    }

    if ( empty( $osoby_ids ) ) return '';

    if ( (int) $atts['limit'] > 0 ) {
        $osoby_ids = array_slice( $osoby_ids, 0, (int) $atts['limit'] );
    }

    $karty = [];

    foreach ( $osoby_ids as $osoba_id ) {

        $imie      = get_field( 'imie', $osoba_id );
        $nazwisko  = get_field( 'nazwisko', $osoba_id );
        $zdjecie   = get_field( 'zdjecie', $osoba_id );
        $permalink = get_permalink( $osoba_id );

        $pelne_imie = trim( esc_html( $nazwisko ) . ' ' . esc_html( $imie ) );
        if ( ! $pelne_imie ) continue;

        if ( $zdjecie ) {
            $img_url      = is_array( $zdjecie ) ? esc_url( $zdjecie['url'] ) : esc_url( $zdjecie );
            $img_alt      = is_array( $zdjecie ) ? esc_attr( $zdjecie['alt'] ) : $pelne_imie;
            $zdjecie_html = '<img src="' . $img_url . '" alt="' . $img_alt . '" class="zs-grid-zdjecie" />';
        } else {
            $litera       = esc_html( mb_substr( $nazwisko ?: $imie, 0, 1 ) );
            $zdjecie_html = '<div class="zs-grid-placeholder" aria-hidden="true"><span>' . $litera . '</span></div>';
        }

        $wewnatrz = $zdjecie_html
            . '<div class="zs-grid-info"><strong class="zs-grid-imie">' . $pelne_imie . '</strong></div>';

        $karty[] = $atts['link'] === 'tak' && $permalink
            ? '<a href="' . esc_url( $permalink ) . '" class="zs-grid-karta zs-grid-karta--link">' . $wewnatrz . '</a>'
            : '<div class="zs-grid-karta">' . $wewnatrz . '</div>';
    }

    if ( empty( $karty ) ) return '';

    $kolumny = max( 1, min( 6, (int) $atts['kolumny'] ) );

    return '<div class="zs-grid" style="--zs-kolumny:' . $kolumny . ';">'
        . implode( '', $karty )
        . '</div>';
} );


/* ============================================================
   SEKCJA 5: LISTA ZGŁOSZEŃ (globalna, strona /zgloszenia)
   Z paginacją, excerptami, osobami i wyborem ilości wyników
   ============================================================ */


/**
 * Pomocnicza — pobiera osoby danej roli dla jednego zgłoszenia.
 * Zwraca tablicę gotowych linków HTML "Nazwisko Imię".
 *
 * @param int    $post_id        ID zgłoszenia
 * @param string $rola           Filtr roli (oprawca|ofiara|swiadek|inny)
 * @param bool   $z_linkiem      Czy owijać w <a>
 * @return array                 Tablica stringów HTML
 */
function zs_pobierz_osoby_roli( $post_id, $rola, $z_linkiem = true ) {

    $powiazane = get_field( 'powiazane_osoby', $post_id );
    if ( ! $powiazane ) return [];

    $wyniki = [];

    foreach ( $powiazane as $lacze ) {

        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze->ID );
        if ( $rola_w_sprawie !== $rola ) continue;

        $osoba_post = get_field( 'osoba', $lacze->ID );
        if ( ! $osoba_post ) continue;

        $imie       = get_field( 'imie', $osoba_post->ID );
        $nazwisko   = get_field( 'nazwisko', $osoba_post->ID );
        $pelne_imie = trim( esc_html( $nazwisko ) . ' ' . esc_html( $imie ) );

        if ( ! $pelne_imie ) continue;

        $wyniki[] = $z_linkiem
            ? '<a href="' . esc_url( get_permalink( $osoba_post->ID ) ) . '">' . $pelne_imie . '</a>'
            : $pelne_imie;
    }

    return $wyniki;
}


/**
 * Pomocnicza — generuje paginację dla listy zgłoszeń.
 * Obsługuje: pierwsza strona, ostatnia strona, poprzednia, następna.
 *
 * @param int $aktualna   Numer aktualnej strony
 * @param int $wszystkich Łączna liczba stron
 * @param int $na_stronie Ile wyników na stronie (do zachowania w URL)
 * @return string         HTML paginacji
 */
function zs_paginacja( $aktualna, $wszystkich, $na_stronie ) {

    if ( $wszystkich <= 1 ) return '';

    $bazowy_url = get_permalink();

    // Buduje URL zachowując parametr per_page
    $url = function( $strona ) use ( $bazowy_url, $na_stronie ) {
        $params = [ 'zs_strona' => $strona ];
        if ( $na_stronie !== 20 ) {
            $params['zs_per'] = $na_stronie;
        }
        return esc_url( add_query_arg( $params, $bazowy_url ) );
    };

    $html = '<nav class="zs-paginacja" aria-label="Paginacja zgłoszeń"><ul>';

    // Pierwsza strona
    if ( $aktualna > 1 ) {
        $html .= '<li><a href="' . $url(1) . '" class="zs-pag-btn zs-pag-pierwsza" title="Pierwsza strona">«</a></li>';
        $html .= '<li><a href="' . $url( $aktualna - 1 ) . '" class="zs-pag-btn zs-pag-prev" title="Poprzednia strona">‹</a></li>';
    } else {
        $html .= '<li><span class="zs-pag-btn zs-pag-disabled">«</span></li>';
        $html .= '<li><span class="zs-pag-btn zs-pag-disabled">‹</span></li>';
    }

    // Numery stron — pokazuj max 5 stron wokół aktualnej
    $od  = max( 1, $aktualna - 2 );
    $do  = min( $wszystkich, $aktualna + 2 );

    if ( $od > 1 ) {
        $html .= '<li><span class="zs-pag-ellipsis">…</span></li>';
    }

    for ( $i = $od; $i <= $do; $i++ ) {
        if ( $i === $aktualna ) {
            $html .= '<li><span class="zs-pag-btn zs-pag-aktywna">' . $i . '</span></li>';
        } else {
            $html .= '<li><a href="' . $url( $i ) . '" class="zs-pag-btn">' . $i . '</a></li>';
        }
    }

    if ( $do < $wszystkich ) {
        $html .= '<li><span class="zs-pag-ellipsis">…</span></li>';
    }

    // Następna / ostatnia
    if ( $aktualna < $wszystkich ) {
        $html .= '<li><a href="' . $url( $aktualna + 1 ) . '" class="zs-pag-btn zs-pag-next" title="Następna strona">›</a></li>';
        $html .= '<li><a href="' . $url( $wszystkich ) . '" class="zs-pag-btn zs-pag-ostatnia" title="Ostatnia strona">»</a></li>';
    } else {
        $html .= '<li><span class="zs-pag-btn zs-pag-disabled">›</span></li>';
        $html .= '<li><span class="zs-pag-btn zs-pag-disabled">»</span></li>';
    }

    $html .= '</ul></nav>';

    return $html;
}


/**
 * Wyświetla wszystkie zgłoszenia w liście z paginacją.
 * Przeznaczony na stronę /zgloszenia.
 *
 * Każda pozycja zawiera:
 *   - Tytuł (link do zgłoszenia)
 *   - Data dodania wpisu
 *   - Ofiara: linki do profili
 *   - Oprawca: linki do profili
 *   - Excerpt (max 255 znaków)
 *
 * Paginacja: przyciski pierwsza/prev/numery/next/ostatnia
 * Wybór ilości wyników: 20, 50, 100 (przez formularz GET)
 *
 * Użycie: [lista_zgloszen]
 * Parametry: domyslnie (domyślna liczba wyników, domyślnie 20)
 */
add_shortcode( 'lista_zgloszen', function( $atts ) {

    $atts = shortcode_atts( [ 'domyslnie' => '20' ], $atts );

    // Odczyt parametrów z URL (GET)
    $dostepne_rozmiary = [ 20, 50, 100 ];
    $na_stronie = isset( $_GET['zs_per'] ) ? (int) $_GET['zs_per'] : (int) $atts['domyslnie'];
    if ( ! in_array( $na_stronie, $dostepne_rozmiary ) ) $na_stronie = 20;

    $aktualna_strona = isset( $_GET['zs_strona'] ) ? max( 1, (int) $_GET['zs_strona'] ) : 1;

    // Zapytanie do bazy
    $query = new WP_Query( [
        'post_type'      => 'zgloszenie',
        'post_status'    => 'publish',
        'posts_per_page' => $na_stronie,
        'paged'          => $aktualna_strona,
        'orderby'        => 'date',
        'order'          => 'DESC',
    ] );

    if ( ! $query->have_posts() ) {
        return '<p class="zs-lista-pusta">Brak zgłoszeń.</p>';
    }

    $wszystkich_stron = $query->max_num_pages;
    $html             = '<div class="zs-lista-zgloszen">';

    // Pasek górny: wyniki + wybór ilości
    $lacznie  = $query->found_posts;
    $od       = ( $aktualna_strona - 1 ) * $na_stronie + 1;
    $do       = min( $aktualna_strona * $na_stronie, $lacznie );
    $base_url = get_permalink();

    $html .= '<div class="zs-lista-header">';
    $html .= '<span class="zs-lista-wyniki">Wyświetlam ' . $od . '–' . $do . ' z ' . $lacznie . ' zgłoszeń</span>';
    $html .= '<div class="zs-lista-per-page">';
    $html .= '<span>Pokaż:</span>';
    foreach ( $dostepne_rozmiary as $rozmiar ) {
        $aktywny = $rozmiar === $na_stronie ? ' zs-per-aktywny' : '';
        $url_per  = esc_url( add_query_arg( [ 'zs_per' => $rozmiar, 'zs_strona' => 1 ], $base_url ) );
        $html    .= '<a href="' . $url_per . '" class="zs-per-btn' . $aktywny . '">' . $rozmiar . '</a>';
    }
    $html .= '</div></div>';

    // Lista zgłoszeń
    $html .= '<ul class="zs-lista">';

    while ( $query->have_posts() ) {
        $query->the_post();

        $post_id    = get_the_ID();
        $tytul      = get_the_title();
        $permalink  = get_permalink();
        $data       = get_the_date( 'd.m.Y' );

        // Excerpt — max 255 znaków, bez HTML
        $excerpt_raw = get_the_excerpt();
        if ( ! $excerpt_raw ) {
            $excerpt_raw = wp_strip_all_tags( get_the_content() );
        }
        $excerpt = mb_strlen( $excerpt_raw ) > 255
            ? mb_substr( $excerpt_raw, 0, 252 ) . '…'
            : $excerpt_raw;

        // Osoby
        $ofiary  = zs_pobierz_osoby_roli( $post_id, 'ofiara' );
        $oprawcy = zs_pobierz_osoby_roli( $post_id, 'oprawca' );

        $html .= '<li class="zs-lista-wpis">';

        // Nagłówek: tytuł + data
        $html .= '<div class="zs-lista-naglowek">';
        $html .= '<a href="' . esc_url( $permalink ) . '" class="zs-lista-tytul">' . esc_html( $tytul ) . '</a>';
        $html .= '<span class="zs-lista-data">' . esc_html( $data ) . '</span>';
        $html .= '</div>';

        // Osoby
        if ( $ofiary || $oprawcy ) {
            $html .= '<div class="zs-lista-osoby">';
            if ( $ofiary ) {
                $html .= '<span class="zs-lista-rola-label">Ofiara:</span> '
                    . implode( ', ', $ofiary );
            }
            if ( $oprawcy ) {
                if ( $ofiary ) $html .= ' &nbsp;';
                $html .= '<span class="zs-lista-rola-label">Oprawca:</span> '
                    . implode( ', ', $oprawcy );
            }
            $html .= '</div>';
        }

        // Excerpt
        if ( $excerpt ) {
            $html .= '<p class="zs-lista-excerpt">' . esc_html( $excerpt ) . '</p>';
        }

        $html .= '</li>';
    }

    wp_reset_postdata();

    $html .= '</ul>';

    // Paginacja dół
    $html .= zs_paginacja( $aktualna_strona, $wszystkich_stron, $na_stronie );

    $html .= '</div>';

    return $html;
} );


/* ============================================================
   SEKCJA 6: CSS
   Wszystkie style pluginu w jednym miejscu.
   Ładowane raz przez wp_head, nie duplikują się przy wielu gridach.
   ============================================================ */

add_action( 'wp_head', function() {

    static $zaladowane = false;
    if ( $zaladowane ) return;
    $zaladowane = true;

    ?>
    <style id="zs-styles">

    /* ----------------------------------------------------------
       ZMIENNE I RESET
    ---------------------------------------------------------- */

    .zs-grid,
    .zs-lista-zgloszen,
    .zs-profil {
        box-sizing: border-box;
    }

    .zs-grid *,
    .zs-lista-zgloszen *,
    .zs-profil * {
        box-sizing: inherit;
    }

    /* ----------------------------------------------------------
       BADGE ROLI (wspólne dla całego pluginu)
    ---------------------------------------------------------- */

    .zs-rola {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.15rem 0.6rem;
        border-radius: 999px;
        vertical-align: middle;
        white-space: nowrap;
    }

    .zs-rola--oprawca { background: #fee2e2; color: #991b1b; }
    .zs-rola--ofiara  { background: #dbeafe; color: #1e40af; }
    .zs-rola--swiadek { background: #d1fae5; color: #065f46; }
    .zs-rola--inny    { background: #f3f4f6; color: #374151; }

    /* ----------------------------------------------------------
       GRID OSÓB [wyswietl_grid]
    ---------------------------------------------------------- */

    .zs-grid {
        display: grid;
        grid-template-columns: repeat(var(--zs-kolumny, 3), 1fr);
        gap: 1.5rem;
        margin: 1.5rem 0;
    }

    .zs-grid-karta {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 1.25rem 1rem;
        transition: box-shadow 0.2s ease, transform 0.2s ease;
    }

    .zs-grid-karta--link {
        text-decoration: none;
        color: inherit;
    }

    .zs-grid-karta--link:hover {
        box-shadow: 0 4px 16px rgba(0,0,0,.10);
        transform: translateY(-2px);
    }

    .zs-grid-zdjecie {
        width: 80px;
        height: 80px;
        object-fit: cover;
        border-radius: 50%;
        margin-bottom: 0.75rem;
        display: block;
    }

    .zs-grid-placeholder {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 0.75rem;
        font-size: 2rem;
        font-weight: 700;
        color: #6b7280;
    }

    .zs-grid-info {
        display: flex;
        flex-direction: column;
        gap: 0.3rem;
    }

    .zs-grid-imie {
        font-size: 0.95rem;
        font-weight: 600;
        color: #111827;
    }

    /* Grid responsywność */
    @media (max-width: 900px) {
        .zs-grid {
            grid-template-columns: repeat(min(var(--zs-kolumny, 3), 2), 1fr);
        }
    }

    @media (max-width: 540px) {
        .zs-grid {
            grid-template-columns: 1fr;
        }

        .zs-grid-karta {
            flex-direction: row;
            text-align: left;
            gap: 1rem;
        }

        .zs-grid-zdjecie,
        .zs-grid-placeholder {
            margin-bottom: 0;
            flex-shrink: 0;
        }
    }

    /* ----------------------------------------------------------
       PROFIL OSOBY [osoba_profil]
    ---------------------------------------------------------- */

    .zs-profil {
        display: flex;
        gap: 2rem;
        align-items: flex-start;
        margin: 1.5rem 0;
    }

    .zs-profil-zdjecie {
        width: 140px;
        height: 140px;
        object-fit: cover;
        border-radius: 50%;
        flex-shrink: 0;
        border: 3px solid #e5e7eb;
    }

    .zs-profil-placeholder {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        background: #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 3.5rem;
        font-weight: 700;
        color: #6b7280;
        flex-shrink: 0;
    }

    .zs-profil-dane {
        flex: 1;
        min-width: 0;
    }

    .zs-profil-imie {
        font-size: 1.75rem;
        font-weight: 700;
        margin: 0 0 1rem;
        color: #111827;
    }

    .zs-profil-sprawy-tytul {
        font-size: 1rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0 0 0.5rem;
    }

    .zs-profil-sprawy-lista {
        list-style: disc;
        padding-left: 1.25rem;
        margin: 0;
        line-height: 2;
    }

    .zs-profil-sprawa a {
        color: #1e40af;
        text-decoration: none;
    }

    .zs-profil-sprawa a:hover {
        text-decoration: underline;
    }

    /* Profil responsywność */
    @media (max-width: 600px) {
        .zs-profil {
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .zs-profil-sprawy-lista {
            text-align: left;
        }
    }

    /* ----------------------------------------------------------
       LISTA POWIĄZAŃ [osoba_powiazania]
    ---------------------------------------------------------- */

    .zs-powiazania-lista {
        list-style: disc;
        padding-left: 1.25rem;
        margin: 1rem 0;
        line-height: 1.9;
    }

    .zs-powiazania-pozycja a {
        color: #1e40af;
        text-decoration: none;
    }

    .zs-powiazania-pozycja a:hover {
        text-decoration: underline;
    }

    /* ----------------------------------------------------------
       LISTA ZGŁOSZEŃ [lista_zgloszen]
    ---------------------------------------------------------- */

    .zs-lista-zgloszen {
        margin: 1.5rem 0;
    }

    /* Pasek górny: wyniki + wybór ilości */
    .zs-lista-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1.25rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #e5e7eb;
    }

    .zs-lista-wyniki {
        font-size: 0.9rem;
        color: #6b7280;
    }

    .zs-lista-per-page {
        display: flex;
        align-items: center;
        gap: 0.4rem;
        font-size: 0.85rem;
        color: #6b7280;
    }

    .zs-per-btn {
        display: inline-block;
        padding: 0.2rem 0.6rem;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        text-decoration: none;
        color: #374151;
        background: #fff;
        transition: background 0.15s, border-color 0.15s;
    }

    .zs-per-btn:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }

    .zs-per-aktywny,
    .zs-per-aktywny:hover {
        background: #1e40af;
        border-color: #1e40af;
        color: #fff;
        pointer-events: none;
    }

    /* Lista wpisów */
    .zs-lista {
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .zs-lista-wpis {
        padding: 1.25rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .zs-lista-wpis:last-child {
        border-bottom: none;
    }

    /* Nagłówek wpisu: tytuł + data */
    .zs-lista-naglowek {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 0.4rem;
    }

    .zs-lista-tytul {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
        text-decoration: none;
        flex: 1;
    }

    .zs-lista-tytul:hover {
        color: #1e40af;
        text-decoration: underline;
    }

    .zs-lista-data {
        font-size: 0.82rem;
        color: #9ca3af;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Osoby pod tytułem */
    .zs-lista-osoby {
        font-size: 0.88rem;
        color: #374151;
        margin-bottom: 0.4rem;
        line-height: 1.6;
    }

    .zs-lista-rola-label {
        font-weight: 600;
        color: #6b7280;
    }

    .zs-lista-osoby a {
        color: #1e40af;
        text-decoration: none;
    }

    .zs-lista-osoby a:hover {
        text-decoration: underline;
    }

    /* Excerpt */
    .zs-lista-excerpt {
        font-size: 0.9rem;
        color: #6b7280;
        line-height: 1.6;
        margin: 0.25rem 0 0;
    }

    /* Pusta lista */
    .zs-lista-pusta {
        color: #9ca3af;
        font-style: italic;
    }

    /* ----------------------------------------------------------
       PAGINACJA
    ---------------------------------------------------------- */

    .zs-paginacja {
        margin: 2rem 0 1rem;
    }

    .zs-paginacja ul {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        list-style: none;
        margin: 0;
        padding: 0;
        align-items: center;
        justify-content: center;
    }

    .zs-pag-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.1rem;
        height: 2.1rem;
        padding: 0 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        font-size: 0.9rem;
        text-decoration: none;
        color: #374151;
        background: #fff;
        transition: background 0.15s, border-color 0.15s, color 0.15s;
    }

    .zs-pag-btn:hover {
        background: #f3f4f6;
        border-color: #9ca3af;
    }

    .zs-pag-aktywna,
    .zs-pag-aktywna:hover {
        background: #1e40af;
        border-color: #1e40af;
        color: #fff;
        pointer-events: none;
        font-weight: 700;
    }

    .zs-pag-disabled {
        color: #d1d5db;
        border-color: #e5e7eb;
        background: #f9fafb;
        pointer-events: none;
    }

    .zs-pag-ellipsis {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 1.5rem;
        height: 2.1rem;
        color: #9ca3af;
        font-size: 1rem;
        user-select: none;
    }

    /* Paginacja responsywność */
    @media (max-width: 480px) {
        .zs-pag-btn {
            min-width: 1.8rem;
            height: 1.8rem;
            font-size: 0.82rem;
        }
    }

    </style>
    <?php
} );


/* ============================================================
   SEKCJA 7: STRONA DOKUMENTACJI W PANELU ADMINA
   Ustawienia > Zgłoszenia Shortcodes
   Czysto informacyjna, bez zapisywania opcji
   ============================================================ */

add_action( 'admin_menu', function() {
    add_options_page(
        'Zgłoszenia Shortcodes',
        'Zgłoszenia Shortcodes',
        'manage_options',
        'zgloszenia-shortcodes',
        'zs_strona_dokumentacji'
    );
} );

function zs_strona_dokumentacji() { ?>
    <div class="wrap">
        <h1>📋 Zgłoszenia Shortcodes — Dokumentacja</h1>
        <p>Architektura: <code>[zgloszenie]</code> → <code>powiazane_osoby</code> → <code>[osoba_w_zgloszeniu]</code> (łącznik z polem <code>rola_w_sprawie</code>) → <code>[osoba]</code></p>
        <hr>

        <h2>Na stronie /zgloszenia (globalna lista)</h2>
        <table class="widefat fixed striped">
            <thead><tr><th>Shortcode</th><th>Parametry</th><th>Opis</th></tr></thead>
            <tbody>
                <tr>
                    <td><code>[lista_zgloszen]</code></td>
                    <td><code>domyslnie</code> (20|50|100)</td>
                    <td>Pełna lista zgłoszeń z tytułem, datą, osobami i excerptami. Paginacja z wyborem 20/50/100 wpisów.</td>
                </tr>
            </tbody>
        </table>

        <h2 style="margin-top:1.5rem;">Na stronie /ofiary, /oprawcy itp. (globalny grid)</h2>
        <table class="widefat fixed striped">
            <thead><tr><th>Shortcode</th><th>Parametry</th><th>Opis</th></tr></thead>
            <tbody>
                <tr>
                    <td><code>[wyswietl_grid]</code></td>
                    <td><code>rola</code>, <code>kolumny</code> (1-6), <code>link</code>, <code>limit</code></td>
                    <td>Grid kart osób filtrowanych po roli. Responsywny (desktop / tablet / mobile).</td>
                </tr>
            </tbody>
        </table>

        <h2 style="margin-top:1.5rem;">Na stronie profilu osoby (CPT: osoba)</h2>
        <table class="widefat fixed striped">
            <thead><tr><th>Shortcode</th><th>Parametry</th><th>Opis</th></tr></thead>
            <tbody>
                <tr>
                    <td><code>[osoba_profil]</code></td>
                    <td><code>link</code></td>
                    <td>Kompletny profil: zdjęcie, H1 z Nazwisko Imię, lista powiązanych spraw z rolami.</td>
                </tr>
                <tr>
                    <td><code>[osoba_powiazania]</code></td>
                    <td><code>link</code></td>
                    <td>Sama lista powiązanych spraw. Użyj gdy chcesz osobno wyświetlić dane.</td>
                </tr>
                <tr>
                    <td><code>[osoba_pole pole="imie"]</code></td>
                    <td><code>pole</code>, <code>prefix</code>, <code>suffix</code></td>
                    <td>Pojedyncze pole ACF osoby: imie, nazwisko, opis, zdjecie.</td>
                </tr>
                <tr>
                    <td><code>[osoba_zgloszenia]</code></td>
                    <td><code>rola</code>, <code>limit</code></td>
                    <td>Filtrowana lista zgłoszeń (tylko jedna rola).</td>
                </tr>
            </tbody>
        </table>

        <h2 style="margin-top:1.5rem;">Na stronie zgłoszenia (CPT: zgloszenie)</h2>
        <table class="widefat fixed striped">
            <thead><tr><th>Shortcode</th><th>Parametry</th><th>Opis</th></tr></thead>
            <tbody>
                <tr>
                    <td><code>[zgloszenie_pelne_imie]</code></td>
                    <td><code>rola</code>, <code>index</code>, <code>separator</code>, <code>link</code></td>
                    <td>Nazwisko Imię wszystkich osób danej roli, oddzielone separatorem.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_imie]</code></td>
                    <td><code>rola</code>, <code>index</code>, <code>separator</code></td>
                    <td>Samo imię.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_nazwisko]</code></td>
                    <td><code>rola</code>, <code>index</code>, <code>separator</code></td>
                    <td>Samo nazwisko.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_zdjecie]</code></td>
                    <td><code>rola</code>, <code>index</code></td>
                    <td>Tag &lt;img&gt; ze zdjęciem.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_opis]</code></td>
                    <td><code>rola</code>, <code>index</code></td>
                    <td>Pole opis z profilu osoby.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_pole pole="data_zdarzenia"]</code></td>
                    <td><code>pole</code>, <code>prefix</code>, <code>suffix</code></td>
                    <td>Pole ACF zgłoszenia: data_zdarzenia, lokalizacja, zrodlo.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_kategorie]</code></td>
                    <td><code>separator</code>, <code>link</code></td>
                    <td>Kategorie taksonomii przypisane do zgłoszenia.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_osoby]</code></td>
                    <td><code>rola</code>, <code>format</code>, <code>pokaz_role</code>, <code>pokaz_zdjecie</code>, <code>link</code></td>
                    <td>Lista osób powiązanych ze zgłoszeniem (ul lub inline).</td>
                </tr>
            </tbody>
        </table>

        <hr>
        <p style="color:#9ca3af;font-size:0.85rem;">
            Wszystkie shortcody zwracają pusty string gdy nie ma danych — bezpieczne w każdym miejscu szablonu.
            CSS ładowany jest raz przez wp_head.
        </p>
    </div>
<?php }
