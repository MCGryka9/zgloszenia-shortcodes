<?php
/**
 * Plugin Name: Zgłoszenia Shortcodes
 * Plugin URI:  #
 * Description: Shortcody do wyświetlania pól ACF dla CPT zgloszenie i osoba
 * Version:     1.0.0
 * Author:      Portal Zgłoszeń
 * Text Domain: zgloszenia-shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// SHORTCODE: [zgloszenie_pole pole="data_zdarzenia"]
// Wyświetla pojedyncze pole ACF z aktualnego zgłoszenia
// Dostępne pola: data_zdarzenia, lokalizacja, zrodlo
// ============================================================
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
        return '<a href="' . esc_url( $wartosc ) . '" target="_blank" rel="noopener">' . esc_html( $wartosc ) . '</a>';
    }

    return esc_html( $atts['prefix'] ) . esc_html( $wartosc ) . esc_html( $atts['suffix'] );
});


// ============================================================
// SHORTCODE: [zgloszenie_kategorie separator=", "]
// Wyświetla kategorie przypisane do aktualnego zgłoszenia
// ============================================================
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
        if ( $atts['link'] === 'tak' ) {
            $lista[] = '<a href="' . esc_url( get_term_link( $termin ) ) . '">' . esc_html( $termin->name ) . '</a>';
        } else {
            $lista[] = esc_html( $termin->name );
        }
    }

    return implode( esc_html( $atts['separator'] ), $lista );
});


// ============================================================
// SHORTCODE: [zgloszenie_osoby rola="oprawca"]
// Wyświetla listę osób powiązanych ze zgłoszeniem
// Parametr rola: oprawca / ofiara / swiadek / (puste = wszyscy)
// Parametr format: lista / inline
// ============================================================
add_shortcode( 'zgloszenie_osoby', function( $atts ) {
    if ( get_post_type() !== 'zgloszenie' ) return '';

    $atts = shortcode_atts( [
        'rola'        => '',
        'format'      => 'lista',
        'pokaz_role'  => 'tak',
        'pokaz_zdjecie' => 'nie',
        'link'        => 'tak',
    ], $atts );

    $powiazane = get_field( 'powiazane_osoby' );
    if ( ! $powiazane ) return '';

    $wyniki = [];

    foreach ( $powiazane as $lacze ) {
        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze->ID );
        $osoba_post     = get_field( 'osoba', $lacze->ID );

        if ( ! $osoba_post ) continue;

        // Filtrowanie po roli
        if ( $atts['rola'] !== '' && $rola_w_sprawie !== $atts['rola'] ) continue;

        $imie     = get_field( 'imie', $osoba_post->ID );
        $nazwisko = get_field( 'nazwisko', $osoba_post->ID );
        $zdjecie  = get_field( 'zdjecie', $osoba_post->ID );
        $pelne    = esc_html( $imie . ' ' . $nazwisko );

        // Link do profilu osoby
        $nazwa_html = $atts['link'] === 'tak'
            ? '<a href="' . esc_url( get_permalink( $osoba_post->ID ) ) . '">' . $pelne . '</a>'
            : $pelne;

        // Zdjęcie
        $zdjecie_html = '';
        if ( $atts['pokaz_zdjecie'] === 'tak' && $zdjecie ) {
            $zdjecie_html = '<img src="' . esc_url( $zdjecie['url'] ) . '" alt="' . $pelne . '" style="width:50px;height:50px;object-fit:cover;border-radius:50%;margin-right:8px;vertical-align:middle;" />';
        }

        // Rola
        $rola_html = '';
        if ( $atts['pokaz_role'] === 'tak' ) {
            $etykiety = [
                'oprawca' => 'Oprawca',
                'ofiara'  => 'Ofiara',
                'swiadek' => 'Świadek',
            ];
            $etykieta  = isset( $etykiety[ $rola_w_sprawie ] ) ? $etykiety[ $rola_w_sprawie ] : $rola_w_sprawie;
            $rola_html = ' <span class="zs-rola zs-rola--' . esc_attr( $rola_w_sprawie ) . '">(' . esc_html( $etykieta ) . ')</span>';
        }

        $wyniki[] = $zdjecie_html . $nazwa_html . $rola_html;
    }

    if ( empty( $wyniki ) ) return '';

    if ( $atts['format'] === 'inline' ) {
        return '<span class="zs-osoby-inline">' . implode( ', ', $wyniki ) . '</span>';
    }

    $html = '<ul class="zs-osoby-lista">';
    foreach ( $wyniki as $wpis ) {
        $html .= '<li class="zs-osoba">' . $wpis . '</li>';
    }
    $html .= '</ul>';

    return $html;
});


// ============================================================
// SHORTCODE: [osoba_pole pole="imie"]
// Wyświetla pojedyncze pole ACF z aktualnej strony osoby
// Dostępne pola: imie, nazwisko, opis, zdjecie
// ============================================================
add_shortcode( 'osoba_pole', function( $atts ) {
    if ( get_post_type() !== 'osoba' ) return '';

    $atts = shortcode_atts( [
        'pole'      => '',
        'prefix'    => '',
        'suffix'    => '',
        'rozmiar'   => 'medium',
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
});


// ============================================================
// SHORTCODE: [osoba_zgloszenia rola="oprawca" limit="10"]
// Na stronie osoby wyświetla listę zgłoszeń w których uczestniczyła
// ============================================================
add_shortcode( 'osoba_zgloszenia', function( $atts ) {
    if ( get_post_type() !== 'osoba' ) return '';

    $atts = shortcode_atts( [
        'rola'   => '',
        'limit'  => 10,
        'format' => 'lista',
    ], $atts );

    $osoba_id = get_the_ID();

    // Znajdź wszystkie łączniki dla tej osoby
    $lacze_query = new WP_Query( [
        'post_type'      => 'osoba_w_zgloszeniu',
        'posts_per_page' => -1,
        'meta_query'     => [
            [
                'key'     => 'osoba',
                'value'   => $osoba_id,
                'compare' => '=',
            ],
        ],
    ] );

    if ( ! $lacze_query->have_posts() ) return '';

    $zgloszenia = [];

    while ( $lacze_query->have_posts() ) {
        $lacze_query->the_post();
        $lacze_id       = get_the_ID();
        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze_id );
        $zgloszenie     = get_field( 'zgloszenie', $lacze_id );

        if ( ! $zgloszenie ) continue;
        if ( $atts['rola'] !== '' && $rola_w_sprawie !== $atts['rola'] ) continue;

        $zgloszenia[] = [
            'post'  => $zgloszenie,
            'rola'  => $rola_w_sprawie,
        ];

        if ( count( $zgloszenia ) >= (int) $atts['limit'] ) break;
    }
    wp_reset_postdata();

    if ( empty( $zgloszenia ) ) return '';

    $etykiety = [
        'oprawca' => 'Oprawca',
        'ofiara'  => 'Ofiara',
        'swiadek' => 'Świadek',
    ];

    $html = '<ul class="zs-zgloszenia-lista">';
    foreach ( $zgloszenia as $z ) {
        $tytul    = esc_html( get_the_title( $z['post']->ID ) );
        $url      = esc_url( get_permalink( $z['post']->ID ) );
        $etykieta = isset( $etykiety[ $z['rola'] ] ) ? $etykiety[ $z['rola'] ] : $z['rola'];
        $html    .= '<li><a href="' . $url . '">' . $tytul . '</a> <span class="zs-rola zs-rola--' . esc_attr( $z['rola'] ) . '">(' . esc_html( $etykieta ) . ')</span></li>';
    }
    $html .= '</ul>';

    return $html;
});


// ============================================================
// STRONA USTAWIEŃ W PANELU ADMINA
// ============================================================
add_action( 'admin_menu', function() {
    add_options_page(
        'Zgłoszenia Shortcodes',
        'Zgłoszenia Shortcodes',
        'manage_options',
        'zgloszenia-shortcodes',
        'zgloszenia_shortcodes_strona_ustawien'
    );
});


/**
 * Funkcja pomocnicza: Pobiera dane osób powiązanych ze zgłoszeniem na podstawie roli i indeksu.
 */
function get_person_field_by_role( $atts, $field_name = 'imie' ) {
    // 1. Parametry shortcode
    $a = shortcode_atts( [
        'rola'      => 'oprawca', // oprawca, ofiara, swiadek
        'index'     => null,      // np. "1" aby pobrać tylko pierwszą osobę z listy
        'post_id'   => get_the_ID(), // pozwala użyć shortcode poza pętlą podając ID zgłoszenia
        'separator' => ', ',      // separator jeśli wyświetlamy wielu
    ], $atts );

    if ( ! $a['post_id'] ) return '';

    // 2. Pobierz powiązane wpisy "osoba_w_zgloszeniu" ze zgłoszenia
    $powiazane = get_field( 'powiazane_osoby', $a['post_id'] );
    if ( ! $powiazane || ! is_array( $powiazane ) ) return '';

    $znalezione_wartosci = [];

    foreach ( $powiazane as $lacze ) {
        // Pobierz dane z łącznika (CPT osoba_w_zgloszeniu)
        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze->ID );

        // Filtrujemy po roli
        if ( $rola_w_sprawie === $a['rola'] ) {
            // Pobierz obiekt CPT "osoba" przypisany do tego łącznika
            $osoba_post = get_field( 'osoba', $lacze->ID );
            
            if ( $osoba_post ) {
                // Pobierz konkretną wartość pola ACF z profilu osoby
                $wartosc = get_field( $field_name, $osoba_post->ID );
                
                if ( $wartosc ) {
                    if ( $field_name === 'zdjecie' ) {
                        // Obsługa zdjęcia (zwraca tag <img>)
                        $url = is_array( $wartosc ) ? $wartosc['url'] : $wartosc;
                        $znalezione_wartosci[] = '<img src="' . esc_url( $url ) . '" class="zs-foto-' . esc_attr($a['rola']) . '">';
                    } else {
                        $znalezione_wartosci[] = esc_html( $wartosc );
                    }
                }
            }
        }
    }

    if ( empty( $znalezione_wartosci ) ) return '';

    // 3. Obsługa parametru INDEX (jeśli podano np. index="1")
    if ( $a['index'] !== null ) {
        $idx = (int)$a['index'] - 1; // Konwersja na 0-based index
        return isset( $znalezione_wartosci[$idx] ) ? $znalezione_wartosci[$idx] : '';
    }

    // 4. Zwróć wszystko połączone separatorem
    return implode( $a['separator'], $znalezione_wartosci );
}

// ============================================================
// REJESTRACJA NOWYCH SHORTCODÓW
// ============================================================

// [zgloszenie_imie rola="oprawca" index="1"]
add_shortcode( 'zgloszenie_imie', function( $atts ) {
    return get_person_field_by_role( $atts, 'imie' );
});

// [zgloszenie_nazwisko rola="ofiara"]
add_shortcode( 'zgloszenie_nazwisko', function( $atts ) {
    return get_person_field_by_role( $atts, 'nazwisko' );
});

// [zgloszenie_zdjecie rola="swiadek"]
add_shortcode( 'zgloszenie_zdjecie', function( $atts ) {
    return get_person_field_by_role( $atts, 'zdjecie' );
});

// [zgloszenie_opis rola="oprawca"]
add_shortcode( 'zgloszenie_opis', function( $atts ) {
    return get_person_field_by_role( $atts, 'opis' );
});

function zgloszenia_shortcodes_strona_ustawien() {
    ?>
    <div class="wrap">
        <h1>📋 Zgłoszenia Shortcodes — Instrukcja Obsługi</h1>
        <p>Moduł został zoptymalizowany pod architekturę: <strong>Zgłoszenie -> Osoba w zgłoszeniu (Łącznik) -> Osoba</strong>.</p>
        <hr>

        <h2>1. Dane osób wewnątrz Zgłoszenia (Dynamiczne)</h2>
        <p>Te shortcode'y działają na stronie pojedynczego <strong>Zgłoszenia</strong>. Wyciągają one dane z powiązanych <strong>Osób</strong>, filtrując je po roli przypisanej w łączniku.</p>
        
        <table class="widefat fixed striped">
            <thead>
                <tr>
                    <th>Shortcode</th>
                    <th>Parametry</th>
                    <th>Opis</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[zgloszenie_imie]</code></td>
                    <td><code>rola</code>, <code>index</code>, <code>separator</code></td>
                    <td>Wyświetla imię osoby/osób o danej roli.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_nazwisko]</code></td>
                    <td><code>rola</code>, <code>index</code>, <code>separator</code></td>
                    <td>Wyświetla nazwisko osoby/osób o danej roli.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_zdjecie]</code></td>
                    <td><code>rola</code>, <code>index</code></td>
                    <td>Wyświetla tag <code>&lt;img&gt;</code> ze zdjęciem profilowym.</td>
                </tr>
            </tbody>
        </table>

        <h3 style="margin-top:20px;">Dostępne parametry dla powyższych:</h3>
        <ul>
            <li><strong><code>rola</code></strong>: <code>oprawca</code>, <code>ofiara</code> lub <code>swiadek</code> (domyślnie: <code>oprawca</code>).</li>
            <li><strong><code>index</code></strong>: Numer osoby na liście (np. <code>index="1"</code> wyświetli tylko pierwszą znalezioną osobę). Jeśli puste, wyświetli wszystkich.</li>
            <li><strong><code>separator</code></strong>: Znak oddzielający osoby, jeśli jest ich kilka (np. <code>separator=" / "</code>).</li>
        </ul>

        <p><strong>Przykłady użycia:</strong></p>
        <code>[zgloszenie_imie rola="oprawca" index="1"]</code> — Imię pierwszego oprawcy.<br>
        <code>[zgloszenie_nazwisko rola="ofiara"]</code> — Nazwiska wszystkich ofiar po przecinku.<br>
        <code>[zgloszenie_zdjecie rola="oprawca" index="1"]</code> — Zdjęcie głównego oprawcy.

        <hr>

        <h2>2. Dane bezpośrednie Zgłoszenia</h2>
        <p>Wyświetla pola ACF przypisane bezpośrednio do wpisu <strong>Zgłoszenie</strong>.</p>
        <table class="widefat fixed striped">
            <thead><tr><th>Pole</th><th>Shortcode</th></tr></thead>
            <tbody>
                <tr><td>Data zdarzenia</td><td><code>[zgloszenie_pole pole="data_zdarzenia"]</code></td></tr>
                <tr><td>Lokalizacja</td><td><code>[zgloszenie_pole pole="lokalizacja"]</code></td></tr>
                <tr><td>Źródło (Link)</td><td><code>[zgloszenie_pole pole="zrodlo"]</code></td></tr>
                <tr><td>Kategorie</td><td><code>[zgloszenie_kategorie]</code></td></tr>
            </tbody>
        </table>

        <hr>

        <h2>3. Dane na stronie profilu Osoby</h2>
        <p>Shortcode'y do użycia wewnątrz CPT <strong>Osoba</strong> (np. w szablonie Single Osoba).</p>
        <ul>
            <li><code>[osoba_pole pole="imie"]</code> — Imię z profilu.</li>
            <li><code>[osoba_pole pole="opis"]</code> — Biografia/Opis osoby.</li>
            <li><code>[osoba_zgloszenia]</code> — Lista wszystkich spraw (Zgłoszeń), w których ta osoba występuje.</li>
        </ul>

        <div style="background: #fff; border-left: 4px solid #00a0d2; padding: 15px; margin-top: 20px;">
            <strong>Pro Tip:</strong> Jeśli chcesz wyświetlić pełne dane (Imię + Nazwisko) jednego konkretnego oprawcy, użyj: <br>
            <code>[zgloszenie_imie rola="oprawca" index="1"] [zgloszenie_nazwisko rola="oprawca" index="1"]</code>
        </div>
    </div>
    <?php
}
