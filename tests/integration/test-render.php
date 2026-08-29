<?php
/**
 * Integration checks — run inside a real WordPress.
 *
 * WHY THESE ARE NOT PHPUNIT.
 *
 * They need a booted WordPress with the plugin active: a block registry, the
 * block parser, `do_blocks()`, and the real `esc_url()` / `wp_kses()` /
 * `get_block_wrapper_attributes()`. Stubbing those in a unit test would mean
 * asserting against our own assumptions rather than against WordPress.
 *
 * The standard answer is wp-env, which needs Docker. This runs against ANY
 * WordPress via wp-cli, including a Local site, so it works without Docker and
 * on CI. See tests/integration/README.md.
 *
 *   wp eval-file tests/integration/test-render.php
 *
 * Exits non-zero on the first failure, so it is usable as a gate.
 *
 * @package BlockKit
 */

// Every notice, warning and deprecation counts as a failure — but only ours.
// A third-party plugin's deprecations are not this suite's business.
error_reporting( E_ALL );

$GLOBALS['bk_notices'] = array();

set_error_handler(
	static function ( $errno, $message, $file, $line ) {
		if ( false !== strpos( $file, 'plugins/blockkit' ) ) {
			$GLOBALS['bk_notices'][] = sprintf( '%s in %s:%d', $message, basename( $file ), $line );
		}
		return false;
	},
	E_ALL
);

/*
 * Counters live in $GLOBALS, not as `global $bk_pass`.
 *
 * `wp eval-file` includes this file from INSIDE a function, so a top-level
 * assignment here is a LOCAL variable — and `global $bk_pass` in bk_check()
 * would then bind to a different, unset variable. The counts stayed at zero
 * while every check printed PASS, which also meant the exit code never fired
 * and this suite was useless as a gate. $GLOBALS is unambiguous either way.
 */
$GLOBALS['bk_pass'] = 0;
$GLOBALS['bk_fail'] = 0;

/**
 * Asserts one condition.
 *
 * @param string $label     What is being checked.
 * @param bool   $condition Result.
 * @return void
 */
function bk_check( $label, $condition ) {
	if ( $condition ) {
		++$GLOBALS['bk_pass'];
		printf( "  \033[32mPASS\033[0m  %s\n", $label );
		return;
	}

	++$GLOBALS['bk_fail'];
	printf( "  \033[31mFAIL\033[0m  %s\n", $label );
}

// ---------------------------------------------------------------------
echo "\nRegistration\n";
// ---------------------------------------------------------------------
$registry = WP_Block_Type_Registry::get_instance();

bk_check( 'blockkit/buttons registered', $registry->is_registered( 'blockkit/buttons' ) );
bk_check( 'blockkit/button registered', $registry->is_registered( 'blockkit/button' ) );

$button = $registry->get_registered( 'blockkit/button' );

bk_check( 'button render callback is callable', $button && is_callable( $button->render_callback ) );
bk_check(
	'editor script handle resolved (script translations wired)',
	$button && ! empty( $button->editor_script_handles )
);
bk_check( 'button declares buttons as parent', $button && in_array( 'blockkit/buttons', (array) $button->parent, true ) );

// ---------------------------------------------------------------------
echo "\nAutoloading and module registry\n";
// ---------------------------------------------------------------------
bk_check( 'interface BlockKit\\Module autoloads', interface_exists( 'BlockKit\\Module' ) );
bk_check( 'BlockKit\\Block\\Registrar implements Module', in_array( 'BlockKit\\Module', (array) class_implements( 'BlockKit\\Block\\Registrar' ), true ) );
bk_check( 'module reachable after boot', BlockKit\Plugin::module( BlockKit\Block\Registrar::class ) instanceof BlockKit\Block\Registrar );
bk_check( 'no legacy global class names remain', ! class_exists( 'BlockKit_Blocks' ) && ! class_exists( 'BlockKit_Block_Render' ) );
bk_check( 'pre-move class names are gone too', ! class_exists( 'BlockKit\\Blocks' ) && ! class_exists( 'BlockKit\\Block_Render' ) );
bk_check( 'sub-namespace classes autoload from includes/block/', class_exists( 'BlockKit\\Block\\Render' ) );

// ---------------------------------------------------------------------
echo "\nRendering — the happy path\n";
// ---------------------------------------------------------------------
$good = '<!-- wp:blockkit/buttons -->'
	. '<!-- wp:blockkit/button {"text":"Click me","url":"https://example.org/?a=1&b=2","icon":"arrow",'
	. '"iconPosition":"left","linkTarget":"_blank","style":{"blockkit":{"width":"200px","iconSize":"1.5em"},'
	. '"@tablet":{"blockkit":{"width":"150px"}},"@mobile":{"blockkit":{"width":"100%","iconSize":"2em"}}}} /-->'
	. '<!-- /wp:blockkit/buttons -->';

$out = do_blocks( $good );

bk_check( 'base width emitted unwrapped', false !== strpos( $out, 'width:200px' ) );
bk_check( 'tablet band emitted', false !== strpos( $out, '150px' ) );
bk_check( 'mobile band emitted', false !== strpos( $out, '100%' ) );
bk_check( 'icon size emitted as a custom property', false !== strpos( $out, '--bk-button-icon-size' ) );
bk_check(
	"core's mutually exclusive ranges, not stacked max-width",
	false !== strpos( $out, 'width <= 480px' ) && false !== strpos( $out, '480px < width' )
);
bk_check( 'icon is aria-hidden', false !== strpos( $out, 'aria-hidden="true"' ) );
bk_check( 'viewBox casing preserved (wp_kses would lowercase it)', false !== strpos( $out, 'viewBox="0 0 20 20"' ) );
bk_check( 'left icon position class applied', false !== strpos( $out, 'has-icon-left' ) );
bk_check( 'noopener added for target=_blank', false !== strpos( $out, 'noopener' ) );
bk_check( 'ampersand not double-encoded', false === strpos( $out, '&amp;#038;' ) );
bk_check( 'inner blocks survived the container save', false !== strpos( $out, 'Click me' ) );
bk_check( 'no diagnostics readout in output', false === strpos( $out, 'stylesProbe' ) );

// ---------------------------------------------------------------------
echo "\nRendering — hostile input\n";
// ---------------------------------------------------------------------
$hostile = '<!-- wp:blockkit/buttons -->'
	. '<!-- wp:blockkit/button {"text":"Hi <script>alert(1)</script><img src=x onerror=alert(1)><strong>bold</strong>",'
	. '"url":"https://ex.org","icon":"arrow","linkTarget":"evil\" onmouseover=\"alert(1)",'
	. '"rel":"noopener\"><script>alert(1)</script>","title":"<script>alert(1)</script>tip",'
	. '"style":{"blockkit":{"width":"expression(alert(1))"},"@mobile":{"blockkit":{"width":"-50px"}}}} /-->'
	. '<!-- wp:blockkit/button {"text":"T2","url":"javascript:alert(1)"} /-->'
	. '<!-- wp:blockkit/button {"text":"T3","url":"data:text/html,<script>alert(1)</script>"} /-->'
	. '<!-- wp:blockkit/button {"text":"T4","url":"https://ex.org","icon":"../../etc/passwd"} /-->'
	. '<!-- /wp:blockkit/buttons -->';

$out = do_blocks( $hostile );

bk_check( 'no <script> anywhere', false === stripos( $out, '<script' ) );
bk_check( 'no onerror handler', false === stripos( $out, 'onerror' ) );
bk_check( 'no onmouseover handler', false === stripos( $out, 'onmouseover' ) );
bk_check( 'no <img> smuggled through the label', false === stripos( $out, '<img' ) );
bk_check( '<strong> kept — the allow-list is not a blanket strip', false !== strpos( $out, '<strong>bold</strong>' ) );
bk_check( 'unrecognised target dropped', false === strpos( $out, 'evil' ) );
bk_check( 'garbage rel token dropped whole', false === strpos( $out, 'noopenerscript' ) );
bk_check( 'title tags stripped, text kept', false !== strpos( $out, 'title="tip"' ) );
bk_check( 'expression() rejected', false === stripos( $out, 'expression(' ) );
bk_check( 'negative width rejected', false === strpos( $out, '-50px' ) );
bk_check( 'javascript: url -> <button>, never href=""', false === stripos( $out, 'javascript:' ) && false === strpos( $out, 'href=""' ) );
bk_check( 'data: url rejected', false === stripos( $out, 'data:text/html' ) );
bk_check( 'unknown icon key ignored', false === strpos( $out, 'passwd' ) );
bk_check( 'no </style> breakout', 1 >= substr_count( $out, '</style>' ) );

// ---------------------------------------------------------------------
echo "\nKit Text — visual presets\n";
// ---------------------------------------------------------------------
bk_check( 'blockkit/text registered', $registry->is_registered( 'blockkit/text' ) );

$out = do_blocks( '<!-- wp:blockkit/text {"styleAs":"caption","content":"Title"} /-->' );

bk_check( 'renders a paragraph', false !== strpos( $out, '<p' ) );
bk_check( 'visual preset applied as a class', false !== strpos( $out, 'has-style-caption' ) );
bk_check( 'preset is a CLASS, not an inline font-size', false === strpos( $out, 'font-size:' ) );

bk_check(
	'an unknown preset is dropped rather than emitted',
	false === strpos( do_blocks( '<!-- wp:blockkit/text {"styleAs":"evil\" onmouseover=\"x","content":"X"} /-->' ), 'onmouseover' )
);
bk_check(
	'and leaves no orphan class behind',
	false === strpos( do_blocks( '<!-- wp:blockkit/text {"styleAs":"nonsense","content":"X"} /-->' ), 'has-style-' )
);

/*
 * A configurable HTML tag is out of scope for now, so the element is fixed.
 * Asserted rather than assumed: if a tag attribute is ever reintroduced, it
 * lands in an ELEMENT POSITION and becomes an XSS boundary, and this check
 * failing is the reminder to validate it against an allow-list.
 */
$out = do_blocks( '<!-- wp:blockkit/text {"tagName":"script","content":"X"} /-->' );

bk_check( 'a stray tagName attribute is ignored entirely', false === stripos( $out, '<script' ) && false !== strpos( $out, '<p' ) );

// ---------------------------------------------------------------------
// ---------------------------------------------------------------------
echo "\nKit Text — content survives a save/load round trip\n";
// ---------------------------------------------------------------------
/*
 * THE TEST THAT WAS MISSING.
 *
 * Every other check here hand-writes the block delimiter, which quietly
 * guarantees the attribute is present. That is not what the editor does, so it
 * could not catch the bug it was hiding: `content` was declared with
 * `"source": "rich-text"`, which tells core to parse the value back out of the
 * SAVED MARKUP — and with `save: () => null` there is no markup, so the text
 * was written nowhere and the block rendered empty on the front end.
 *
 * serialize_blocks() is the PHP mirror of the JS serializer, so going
 * attributes -> serialize -> parse -> render exercises the real path.
 */
$round_trip = serialize_blocks(
	array(
		array(
			'blockName'    => 'blockkit/text',
			'attrs'        => array(
				'content' => 'Round trip text',
				'styleAs' => 'eyebrow',
			),
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		),
	)
);

bk_check(
	'content is serialised into the block delimiter',
	false !== strpos( $round_trip, 'Round trip text' )
);

$out = do_blocks( $round_trip );

bk_check( 'content survives serialise -> parse -> render', false !== strpos( $out, 'Round trip text' ) );
bk_check( 'styleAs survives the round trip', false !== strpos( $out, 'has-style-eyebrow' ) );

/*
 * And the same thing through a real post and the_content, because that is the
 * path a visitor actually hits.
 */
$probe_id = wp_insert_post(
	array(
		'post_title'   => 'BlockKit integration probe',
		'post_status'  => 'publish',
		'post_type'    => 'post',
		'post_content' => $round_trip,
	)
);

if ( $probe_id && ! is_wp_error( $probe_id ) ) {
	$probe_post = get_post( $probe_id );
	$front_end  = apply_filters( 'the_content', $probe_post->post_content );

	bk_check( 'content is visible through the_content on a real post', false !== strpos( $front_end, 'Round trip text' ) );

	wp_delete_post( $probe_id, true );
} else {
	bk_check( 'could create a probe post', false );
}

// ---------------------------------------------------------------------
echo "\nEdge cases\n";
// ---------------------------------------------------------------------
bk_check(
	'empty label renders nothing at all',
	'' === trim( do_blocks( '<!-- wp:blockkit/button {"text":"  "} /-->' ) )
);
bk_check(
	'empty container renders nothing at all',
	'' === trim( do_blocks( '<!-- wp:blockkit/buttons --><!-- /wp:blockkit/buttons -->' ) )
);
bk_check(
	'empty text renders nothing at all',
	'' === trim( do_blocks( '<!-- wp:blockkit/text {"content":"  "} /-->' ) )
);

// ---------------------------------------------------------------------
echo "\nPHP notices from plugin files\n";
// ---------------------------------------------------------------------
$notices = array_unique( $GLOBALS['bk_notices'] );

bk_check( 'no notices, warnings or deprecations', empty( $notices ) );

foreach ( $notices as $notice ) {
	printf( "        %s\n", $notice );
}

// ---------------------------------------------------------------------
printf( "\n%d passed, %d failed\n\n", $GLOBALS['bk_pass'], $GLOBALS['bk_fail'] );

if ( $GLOBALS['bk_fail'] > 0 ) {
	exit( 1 );
}
