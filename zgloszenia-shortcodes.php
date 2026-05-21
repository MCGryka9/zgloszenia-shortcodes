<?php

/**
 * Plugin Name: Zgłoszenia Shortcodes
 * Plugin URI: Gryczan.eu
 * Description: Shortcody do wyświetlania pól ACF dla CPT zgloszenie i osoba
 * Version: 1.1.0
 * Author: Gryczan.eu
 * Text Domain: zgloszenia-shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// ============================================================
// SHORTCODE: [zgloszenie_pole pole="data_zdarzenia"]
// ============================================================

add_shortcode( 'zgloszenie_pole', function( $atts ) {
    if ( get_post_type() !== 'zgloszenie' ) return '';
    $atts = shortcode_atts( [ 'pole' => '', 'prefix' => '', 'suffix' => '' ], $atts );
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
// ============================================================

add_shortcode( 'zgloszenie_kategorie', function( $atts ) {
    if ( get_post_type() !== 'zgloszenie' ) return '';
    $atts = shortcode_atts( [ 'separator' => ', ', 'link' => 'tak' ], $atts );
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
// ============================================================

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
    $wyniki = [];
    foreach ( $powiazane as $lacze ) {
        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze->ID );
        $osoba_post     = get_field( 'osoba', $lacze->ID );
        if ( ! $osoba_post ) continue;
        if ( $atts['rola'] !== '' && $rola_w_sprawie !== $atts['rola'] ) continue;
        $imie    = get_field( 'imie', $osoba_post->ID );
        $nazwisko = get_field( 'nazwisko', $osoba_post->ID );
        $zdjecie  = get_field( 'zdjecie', $osoba_post->ID );
        $pelne   = esc_html( $imie . ' ' . $nazwisko );
        $nazwa_html = $atts['link'] === 'tak'
            ? '<a href="' . esc_url( get_permalink( $osoba_post->ID ) ) . '">' . $pelne . '</a>'
            : $pelne;
        $zdjecie_html = '';
        if ( $atts['pokaz_zdjecie'] === 'tak' && $zdjecie ) {
            $zdjecie_html = '<img src="' . esc_url( $zdjecie['url'] ) . '" alt="' . $pelne . '" style="width:50px;height:50px;object-fit:cover;border-radius:50%;margin-right:8px;vertical-align:middle;" />';
        }
        $rola_html = '';
        if ( $atts['pokaz_role'] === 'tak' ) {
            $etykiety  = [ 'oprawca' => 'Oprawca', 'ofiara' => 'Ofiara', 'swiadek' => 'Świadek' ];
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
// ============================================================

add_shortcode( 'osoba_pole', function( $atts ) {
    if ( get_post_type() !== 'osoba' ) return '';
    $atts = shortcode_atts( [ 'pole' => '', 'prefix' => '', 'suffix' => '', 'rozmiar' => 'medium' ], $atts );
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
// ============================================================

add_shortcode( 'osoba_zgloszenia', function( $atts ) {
    if ( get_post_type() !== 'osoba' ) return '';
    $atts = shortcode_atts( [ 'rola' => '', 'limit' => 10, 'format' => 'lista' ], $atts );
    $osoba_id    = get_the_ID();
    $lacze_query = new WP_Query( [
        'post_type'      => 'osoba_w_zgloszeniu',
        'posts_per_page' => -1,
        'meta_query'     => [ [ 'key' => 'osoba', 'value' => $osoba_id, 'compare' => '=' ] ],
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
        $zgloszenia[] = [ 'post' => $zgloszenie, 'rola' => $rola_w_sprawie ];
        if ( count( $zgloszenia ) >= (int) $atts['limit'] ) break;
    }
    wp_reset_postdata();
    if ( empty( $zgloszenia ) ) return '';
    $etykiety = [ 'oprawca' => 'Oprawca', 'ofiara' => 'Ofiara', 'swiadek' => 'Świadek' ];
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
// FUNKCJA POMOCNICZA: Pobiera pole z osób powiązanych ze zgłoszeniem
// ============================================================

function get_person_field_by_role( $atts, $field_name = 'imie' ) {
    $a = shortcode_atts( [
        'rola'      => 'oprawca',
        'index'     => null,
        'post_id'   => get_the_ID(),
        'separator' => ', ',
    ], $atts );
    if ( ! $a['post_id'] ) return '';
    $powiazane = get_field( 'powiazane_osoby', $a['post_id'] );
    if ( ! $powiazane || ! is_array( $powiazane ) ) return '';
    $znalezione_wartosci = [];
    foreach ( $powiazane as $lacze ) {
        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze->ID );
        if ( $rola_w_sprawie === $a['rola'] ) {
            $osoba_post = get_field( 'osoba', $lacze->ID );
            if ( $osoba_post ) {
                $wartosc = get_field( $field_name, $osoba_post->ID );
                if ( $wartosc ) {
                    if ( $field_name === 'zdjecie' ) {
                        $url = is_array( $wartosc ) ? $wartosc['url'] : $wartosc;
                        $znalezione_wartosci[] = '<img src="' . esc_url( $url ) . '" class="zs-foto-' . esc_attr( $a['rola'] ) . '">';
                    } else {
                        $znalezione_wartosci[] = esc_html( $wartosc );
                    }
                }
            }
        }
    }
    if ( empty( $znalezione_wartosci ) ) return '';
    if ( $a['index'] !== null ) {
        $idx = (int) $a['index'] - 1;
        return isset( $znalezione_wartosci[ $idx ] ) ? $znalezione_wartosci[ $idx ] : '';
    }
    return implode( $a['separator'], $znalezione_wartosci );
}

// ============================================================
// SHORTCODE: [zgloszenie_imie rola="oprawca" index="1"]
// ============================================================
add_shortcode( 'zgloszenie_imie', function( $atts ) {
    return get_person_field_by_role( $atts, 'imie' );
});

// ============================================================
// SHORTCODE: [zgloszenie_nazwisko rola="ofiara"]
// ============================================================
add_shortcode( 'zgloszenie_nazwisko', function( $atts ) {
    return get_person_field_by_role( $atts, 'nazwisko' );
});

// ============================================================
// SHORTCODE: [zgloszenie_zdjecie rola="swiadek"]
// ============================================================
add_shortcode( 'zgloszenie_zdjecie', function( $atts ) {
    return get_person_field_by_role( $atts, 'zdjecie' );
});

// ============================================================
// SHORTCODE: [zgloszenie_opis rola="oprawca"]
// ============================================================
add_shortcode( 'zgloszenie_opis', function( $atts ) {
    return get_person_field_by_role( $atts, 'opis' );
});

// ============================================================
// NOWY SHORTCODE: [zgloszenie_pelne_imie rola="oprawca"]
// Wyświetla "Nazwisko Imię" wszystkich osób o danej roli.
// Jeśli więcej niż jedna osoba — oddziela ", ".
// Parametry: rola, index, separator, post_id, link
// ============================================================

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
        // Kolejność: Nazwisko Imię
        $pelne = trim( esc_html( $nazwisko ) . ' ' . esc_html( $imie ) );
        if ( $atts['link'] === 'tak' ) {
            $pelne = '<a href="' . esc_url( get_permalink( $osoba_post->ID ) ) . '">' . $pelne . '</a>';
        }
        $wyniki[] = $pelne;
    }
    if ( empty( $wyniki ) ) return '';
    // Obsługa index (1-based)
    if ( $atts['index'] !== null ) {
        $idx = (int) $atts['index'] - 1;
        return isset( $wyniki[ $idx ] ) ? $wyniki[ $idx ] : '';
    }
    return implode( esc_html( $atts['separator'] ), $wyniki );
});

// ============================================================
// SHORTCODE: [wyswietl_grid rola="ofiara"]
// Globalna strona (np. /ofiary) — wyświetla WSZYSTKIE osoby CPT "osoba"
// które mają powiązany łącznik osoba_w_zgloszeniu z daną rolą.
// Parametry: rola (oprawca/ofiara/swiadek/inny, puste=wszyscy),
//            kolumny (1-6, domyślnie 3), link (tak/nie), limit (-1=wszyscy)
// ============================================================

add_shortcode( 'wyswietl_grid', function( $atts ) {
    $atts = shortcode_atts( [
        'rola'    => '',
        'kolumny' => '3',
        'link'    => 'tak',
        'limit'   => -1,
    ], $atts );

    // Buduj meta_query dla łącznika — szukamy CPT osoba_w_zgloszeniu
    // które mają odpowiednią rolę, a następnie pobieramy unikalne osoby
    $lacze_args = [
        'post_type'      => 'osoba_w_zgloszeniu',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ];

    if ( $atts['rola'] !== '' ) {
        $lacze_args['meta_query'] = [
            [
                'key'     => 'rola_w_sprawie',
                'value'   => $atts['rola'],
                'compare' => '=',
            ],
        ];
    }

    $lacze_ids = get_posts( $lacze_args );
    if ( empty( $lacze_ids ) ) return '';

    // Zbierz unikalne ID osób (CPT osoba) z łączników
    $osoby_ids = [];
    foreach ( $lacze_ids as $lacze_id ) {
        $osoba_post = get_field( 'osoba', $lacze_id );
        if ( $osoba_post && ! in_array( $osoba_post->ID, $osoby_ids ) ) {
            $osoby_ids[] = $osoba_post->ID;
        }
    }

    if ( empty( $osoby_ids ) ) return '';

    // Ogranicz limit jeśli podano
    if ( (int) $atts['limit'] > 0 ) {
        $osoby_ids = array_slice( $osoby_ids, 0, (int) $atts['limit'] );
    }

    // Buduj karty
    $karty = [];

    foreach ( $osoby_ids as $osoba_id ) {
        $imie      = get_field( 'imie', $osoba_id );
        $nazwisko  = get_field( 'nazwisko', $osoba_id );
        $zdjecie   = get_field( 'zdjecie', $osoba_id );
        $permalink = get_permalink( $osoba_id );

        $pelne_imie = trim( esc_html( $nazwisko ) . ' ' . esc_html( $imie ) );
        if ( ! $pelne_imie ) continue;

        // Zdjęcie lub placeholder z pierwszą literą nazwiska
        if ( $zdjecie ) {
            $img_url      = is_array( $zdjecie ) ? esc_url( $zdjecie['url'] ) : esc_url( $zdjecie );
            $img_alt      = is_array( $zdjecie ) ? esc_attr( $zdjecie['alt'] ) : $pelne_imie;
            $zdjecie_html = '<img src="' . $img_url . '" alt="' . $img_alt . '" class="zs-grid-zdjecie" />';
        } else {
            $litera       = esc_html( mb_substr( $nazwisko ?: $imie, 0, 1 ) );
            $zdjecie_html = '<div class="zs-grid-placeholder" aria-hidden="true"><span>' . $litera . '</span></div>';
        }

        $karta_wewnatrz = '
            ' . $zdjecie_html . '
            <div class="zs-grid-info">
                <strong class="zs-grid-imie">' . $pelne_imie . '</strong>
            </div>';

        if ( $atts['link'] === 'tak' && $permalink ) {
            $karty[] = '<a href="' . esc_url( $permalink ) . '" class="zs-grid-karta zs-grid-karta--link">' . $karta_wewnatrz . '</a>';
        } else {
            $karty[] = '<div class="zs-grid-karta">' . $karta_wewnatrz . '</div>';
        }
    }

    if ( empty( $karty ) ) return '';

    $kolumny = max( 1, min( 6, (int) $atts['kolumny'] ) );
    $html    = '<div class="zs-grid" style="--zs-kolumny:' . $kolumny . ';">';
    foreach ( $karty as $karta ) {
        $html .= $karta;
    }
    $html .= '</div>';

    return $html;
});

// ============================================================
// SHORTCODE: [osoba_powiazania]
// Używany na stronie profilu osoby (CPT "osoba").
// Wyświetla listę wszystkich zgłoszeń powiązanych z tą osobą + ich rola.
// Format: • Tytuł zgłoszenia — Rola
// Parametry: link (tak/nie)
// ============================================================

add_shortcode( 'osoba_powiazania', function( $atts ) {
    if ( get_post_type() !== 'osoba' ) return '';

    $atts = shortcode_atts( [
        'link' => 'tak',
    ], $atts );

    $osoba_id = get_the_ID();

    $etykiety_rol = [
        'oprawca' => 'Oprawca',
        'ofiara'  => 'Ofiara',
        'swiadek' => 'Świadek',
        'inny'    => 'Inny',
    ];

    // Znajdź wszystkie łączniki osoba_w_zgloszeniu dla tej osoby
    $lacze_ids = get_posts( [
        'post_type'      => 'osoba_w_zgloszeniu',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_query'     => [
            [
                'key'     => 'osoba',
                'value'   => $osoba_id,
                'compare' => '=',
            ],
        ],
    ] );

    if ( empty( $lacze_ids ) ) return '';

    $pozycje = [];

    foreach ( $lacze_ids as $lacze_id ) {
        $zgloszenie     = get_field( 'zgloszenie', $lacze_id );
        $rola_w_sprawie = get_field( 'rola_w_sprawie', $lacze_id );

        if ( ! $zgloszenie ) continue;

        $tytul    = get_the_title( $zgloszenie->ID );
        $url      = get_permalink( $zgloszenie->ID );
        $etykieta = isset( $etykiety_rol[ $rola_w_sprawie ] ) ? $etykiety_rol[ $rola_w_sprawie ] : esc_html( $rola_w_sprawie );

        $tytul_html = $atts['link'] === 'tak'
            ? '<a href="' . esc_url( $url ) . '">' . esc_html( $tytul ) . '</a>'
            : esc_html( $tytul );

        $pozycje[] = $tytul_html
            . ' — <span class="zs-rola zs-rola--' . esc_attr( $rola_w_sprawie ) . '">'
            . esc_html( $etykieta )
            . '</span>';
    }

    if ( empty( $pozycje ) ) return '';

    $html = '<ul class="zs-powiazania-lista">';
    foreach ( $pozycje as $p ) {
        $html .= '<li class="zs-powiazania-pozycja">' . $p . '</li>';
    }
    $html .= '</ul>';

    return $html;
});

// ============================================================
// CSS GRIDA — ładowany raz na stronę przez wp_head
// ============================================================

add_action( 'wp_head', function() {
    static $juz_zaladowane = false;
    if ( $juz_zaladowane ) return;
    $juz_zaladowane = true;
    ?>
    <style id="zs-grid-styles">

        /* ---- Grid kontener ---- */
        .zs-grid {
            display: grid;
            grid-template-columns: repeat(var(--zs-kolumny, 3), 1fr);
            gap: 1.5rem;
            margin: 1.5rem 0;
        }

        /* ---- Karta ---- */
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
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.10);
            transform: translateY(-2px);
        }

        /* ---- Zdjęcie ---- */
        .zs-grid-zdjecie {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            margin-bottom: 0.75rem;
            display: block;
        }

        /* ---- Placeholder (brak zdjęcia) ---- */
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

        /* ---- Info pod zdjęciem ---- */
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

        .zs-grid-rola {
            font-size: 0.78rem;
            font-weight: 500;
            padding: 0.15rem 0.6rem;
            border-radius: 999px;
            display: inline-block;
        }

        /* ---- Kolory ról ---- */
        .zs-rola--oprawca { background: #fee2e2; color: #991b1b; }
        .zs-rola--ofiara  { background: #dbeafe; color: #1e40af; }
        .zs-rola--swiadek { background: #d1fae5; color: #065f46; }
        .zs-rola--inny    { background: #f3f4f6; color: #374151; }

        /* ---- Lista powiązań osoby [osoba_powiazania] ---- */
        .zs-powiazania-lista {
            list-style: disc;
            padding-left: 1.25rem;
            margin: 1rem 0;
            line-height: 1.8;
        }

        .zs-powiazania-pozycja .zs-rola {
            font-size: 0.78rem;
            font-weight: 500;
            padding: 0.1rem 0.5rem;
            border-radius: 999px;
            vertical-align: middle;
        }

        /* ---- Responsywność ---- */

        /* Tablet: max 2 kolumny */
        @media (max-width: 900px) {
            .zs-grid {
                grid-template-columns: repeat(
                    min(var(--zs-kolumny, 3), 2),
                    1fr
                );
            }
        }

        /* Mobile: zawsze 1 kolumna, karta pozioma */
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

    </style>
    <?php
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

function zgloszenia_shortcodes_strona_ustawien() {
    ?>
    <div class="wrap">
        <h1>📋 Zgłoszenia Shortcodes — Instrukcja Obsługi</h1>
        <p>Moduł pod architekturę: <strong>Zgłoszenie → Osoba w zgłoszeniu (Łącznik) → Osoba</strong>.</p>
        <hr>

        <h2>1. Dane osób wewnątrz Zgłoszenia</h2>
        <table class="widefat fixed striped">
            <thead>
                <tr><th>Shortcode</th><th>Parametry</th><th>Opis</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[zgloszenie_imie]</code></td>
                    <td><code>rola</code>, <code>index</code>, <code>separator</code></td>
                    <td>Imię osoby/osób o danej roli.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_nazwisko]</code></td>
                    <td><code>rola</code>, <code>index</code>, <code>separator</code></td>
                    <td>Nazwisko osoby/osób o danej roli.</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_pelne_imie]</code></td>
                    <td><code>rola</code>, <code>index</code>, <code>separator</code>, <code>link</code></td>
                    <td><strong>Nazwisko Imię</strong> — jednym wywołaniem. Wiele osób oddziela separatorem (domyślnie <code>, </code>).</td>
                </tr>
                <tr>
                    <td><code>[zgloszenie_zdjecie]</code></td>
                    <td><code>rola</code>, <code>index</code></td>
                    <td>Tag <code>&lt;img&gt;</code> ze zdjęciem profilowym.</td>
                </tr>
                <tr>
                    <td><code>[wyswietl_grid]</code></td>
                    <td><code>rola</code>, <code>kolumny</code>, <code>link</code>, <code>limit</code></td>
                    <td>Globalny responsywny grid — wyświetla wszystkie osoby CPT "osoba" o danej roli. Używaj na dowolnej stronie, niezależnie od zgłoszenia.</td>
                </tr>
            </tbody>
        </table>

        <h3 style="margin-top:20px;">Parametry:</h3>
        <ul>
            <li><strong><code>rola</code></strong>: <code>oprawca</code> / <code>ofiara</code> / <code>swiadek</code> / <code>inny</code> (lub puste = wszyscy).</li>
            <li><strong><code>index</code></strong>: Numer osoby (1-based). Puste = wszyscy.</li>
            <li><strong><code>separator</code></strong>: Separator między osobami (domyślnie <code>, </code>).</li>
            <li><strong><code>link</code></strong>: <code>tak</code> (domyślnie) / <code>nie</code>.</li>
            <li><strong><code>kolumny</code></strong> (tylko grid): 1–6, domyślnie <code>3</code>.</li>
        </ul>

        <p><strong>Przykłady:</strong><br>
            <code>[zgloszenie_pelne_imie rola="oprawca"]</code> → <em>Kowalski Jan, Nowak Adam</em><br>
            <code>[zgloszenie_pelne_imie rola="ofiara" index="1"]</code> → pierwsza ofiara<br>
            <code>[wyswietl_grid rola="ofiara"]</code> → wszystkie ofiary na stronie /ofiary<br>
            <code>[wyswietl_grid rola="oprawca" kolumny="4" link="nie"]</code> → 4 kolumny bez linków<br>
            <code>[wyswietl_grid]</code> → wszystkie osoby bez filtra roli
        </p>

        <hr>

        <h2>3. Dane bezpośrednie Zgłoszenia</h2>
        <table class="widefat fixed striped">
            <thead><tr><th>Pole</th><th>Shortcode</th></tr></thead>
            <tbody>
                <tr><td>Data zdarzenia</td><td><code>[zgloszenie_pole pole="data_zdarzenia"]</code></td></tr>
                <tr><td>Lokalizacja</td><td><code>[zgloszenie_pole pole="lokalizacja"]</code></td></tr>
                <tr><td>Źródło (Link)</td><td><code>[zgloszenie_pole pole="zrodlo"]</code></td></tr>
                <tr><td>Kategorie</td><td><code>[zgloszenie_kategorie]</code></td></tr>
            </tbody>
        </table>
    </div>
        <table class="widefat fixed striped">
            <thead>
                <tr><th>Shortcode</th><th>Parametry</th><th>Opis</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>[osoba_pole pole="imie"]</code></td>
                    <td><code>pole</code>, <code>prefix</code>, <code>suffix</code></td>
                    <td>Pole ACF z profilu osoby (imie, nazwisko, opis, zdjecie).</td>
                </tr>
                <tr>
                    <td><code>[osoba_powiazania]</code></td>
                    <td><code>link</code></td>
                    <td>Lista zgłoszeń powiązanych z tą osobą w formacie <em>Tytuł zgłoszenia — Rola</em>. Używaj na stronie profilu osoby.</td>
                </tr>
                <tr>
                    <td><code>[osoba_zgloszenia]</code></td>
                    <td><code>rola</code>, <code>limit</code></td>
                    <td>Filtrowana lista spraw — tylko zgłoszenia gdzie osoba miała daną rolę.</td>
                </tr>
            </tbody>
        </table>
    <?php
}
