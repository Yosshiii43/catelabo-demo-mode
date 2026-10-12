<?php
/**
 * Plugin Name: Catelabo Demo Mode
 * Description: デモサイト専用の3層明示(上部常時バー+フッター注記/CF7送信無効化/noindex,nofollow)と、SNSアイコンのデモ表示(リンク無効)。デモ環境でのみ有効化すること。仕様正本: product/docs/demosite_design_v1_0.md ⑥
 * Version: 1.1.1
 * Author: Mew Works (Catelabo)
 * Author URI: https://catelabo.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * catelabo-baseテーマ本体には一切手を入れない前提の単一ファイルプラグイン。
 * 有効化するだけで動作し、削除・無効化すれば痕跡なく元に戻る。
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================
 * 設定(文言・リンク先)— 仕様正本 demosite_design_v1_0.md ⑥ の確定文言
 * ======================================================= */
const CATELABO_DEMO_NOTICE_TEXT = 'このサイトはキャテラボの制作デモです(架空の猫舎です)';
const CATELABO_DEMO_LINK_URL    = 'https://catelabo.com/service/?utm_source=demo&utm_medium=bar'; // catelabo.com公開済み(2026-10-06)。デモ上部バーからサービスページへ(product/docs/catelabo.com_CTA導線設計_v1.1.md §5)
const CATELABO_DEMO_LINK_LABEL  = '料金・サービスを見る →';
const CATELABO_DEMO_FORM_TEXT   = 'デモサイトのため送信されません';
const CATELABO_DEMO_SNS_TEXT    = 'デモサイトのため、SNSへのリンクは無効です';
const CATELABO_DEMO_SNS_HREF    = '#catelabo-demo-sns';

/** デモで並べるSNS(猫舎がよく使う組み合わせ)。キーは catelabo-base の catelabo_sns_services() に合わせる */
function catelabo_demo_sns_shown() {
	return apply_filters( 'catelabo_demo_sns_shown', array( 'instagram', 'youtube', 'tiktok', 'line' ) );
}

/** リンク先・ラベルは他案件デモで流用する際、テーマ側の functions.php から上書き可能 */
function catelabo_demo_link_url() {
	return apply_filters( 'catelabo_demo_link_url', CATELABO_DEMO_LINK_URL );
}
function catelabo_demo_link_label() {
	return apply_filters( 'catelabo_demo_link_label', CATELABO_DEMO_LINK_LABEL );
}

/* =========================================================
 * 層1: 上部32px常時バー + フッター同文
 * ネイビー #2C4770 + 白文字。警告色は使わない静かなデザイン。
 * ======================================================= */

/** バー/フッター共通のスタイル(フロントのみ) */
function catelabo_demo_styles() {
	?>
	<style id="catelabo-demo-mode-css">
		.catelabo-demo-bar{
			box-sizing:border-box;
			min-height:32px;
			display:flex;
			align-items:center;
			justify-content:center;
			flex-wrap:wrap;
			gap:.25em 1em;
			padding:6px 16px;
			background:#2C4770;
			color:#fff;
			font-size:12px;
			line-height:1.5;
			text-align:center;
			letter-spacing:.02em;
		}
		.catelabo-demo-bar a{
			color:#fff;
			text-decoration:underline;
			text-underline-offset:2px;
		}
		.catelabo-demo-bar a:hover{
			opacity:.8;
		}
		/* 文言の区切り。1行に入らないときは「(」の前で折り返す */
		.catelabo-demo-bar__part{
			display:inline-block;
		}
	</style>
	<?php
}
add_action( 'wp_head', 'catelabo_demo_styles' );

/**
 * 告知文のHTML。「(」の前で区切り、各部分を折り返さない塊にする。
 * 横に入りきらないとき、「(架空の猫舎です)」の途中で改行されるのを防ぐ。
 */
function catelabo_demo_notice_html() {
	$parts = preg_split( '/(?=\()/u', CATELABO_DEMO_NOTICE_TEXT, -1, PREG_SPLIT_NO_EMPTY );
	$html  = '';
	foreach ( $parts as $part ) {
		$html .= '<span class="catelabo-demo-bar__part">' . esc_html( $part ) . '</span>';
	}
	return $html;
}

/** バー本体のマークアップ */
function catelabo_demo_bar_markup() {
	$url = catelabo_demo_link_url();
	if ( empty( $url ) ) {
		printf(
			'<div class="catelabo-demo-bar" role="note"><span>%s</span></div>',
			catelabo_demo_notice_html() // 中で esc_html 済み
		);
		return;
	}
	printf(
		'<div class="catelabo-demo-bar" role="note"><span>%s</span><a href="%s">%s</a></div>',
		catelabo_demo_notice_html(), // 中で esc_html 済み
		esc_url( $url ),
		esc_html( catelabo_demo_link_label() )
	);
}

/** wp_body_open が呼ばれたかのフラグ(古いテーマ向けフォールバック用) */
function catelabo_demo_top_bar() {
	static $done = false;
	if ( $done ) {
		return;
	}
	$done = true;
	catelabo_demo_bar_markup();
}
add_action( 'wp_body_open', 'catelabo_demo_top_bar' );

/** フッター注記 + wp_body_open未対応テーマ向けフォールバック */
function catelabo_demo_footer_notice() {
	// フッターにも同文(リンク付き)
	catelabo_demo_bar_markup();

	// テーマが wp_body_open を呼んでいない場合のみ、バーをbody先頭へ移動する保険
	if ( ! did_action( 'wp_body_open' ) ) {
		catelabo_demo_bar_markup();
		?>
		<script id="catelabo-demo-mode-fallback">
			(function () {
				var bars = document.querySelectorAll('.catelabo-demo-bar');
				if (bars.length > 1) {
					document.body.prepend(bars[bars.length - 1]);
				}
			})();
		</script>
		<?php
	}
}
add_action( 'wp_footer', 'catelabo_demo_footer_notice', 5 );

/* =========================================================
 * 層2: フォーム送信無効化(Contact Form 7)
 * UIは通常どおり。送信押下でメッセージ表示のみ、メール発信なし。
 * ======================================================= */

// メール送信を全フォームでスキップ(CF7が「送信成功」として扱う)
add_filter( 'wpcf7_skip_mail', '__return_true' );

// 成功メッセージをデモ用文言に差し替え
function catelabo_demo_cf7_message( $message, $status ) {
	if ( 'mail_sent_ok' === $status ) {
		return CATELABO_DEMO_FORM_TEXT;
	}
	return $message;
}
add_filter( 'wpcf7_display_message', 'catelabo_demo_cf7_message', 10, 2 );

// Flamingo等が入っていても受信メッセージを保存しない(保険)
add_filter( 'wpcf7_flamingo_submit_if', '__return_empty_array' );

/* =========================================================
 * 層3: noindex, nofollow
 * 検索流入を遮断。到達経路はDM・Instagram・料金ページのリンクのみ。
 * ======================================================= */

// metaタグ(WP5.7+ の wp_robots API)
function catelabo_demo_robots( $robots ) {
	$robots['noindex']  = true;
	$robots['nofollow'] = true;
	unset( $robots['max-image-preview'] ); // noindex時は不要
	return $robots;
}
add_filter( 'wp_robots', 'catelabo_demo_robots' );

// HTTPヘッダーでも遮断(フィード・添付など非HTML応答もカバー)
function catelabo_demo_robots_header() {
	if ( ! is_admin() && ! headers_sent() ) {
		header( 'X-Robots-Tag: noindex, nofollow', true );
	}
}
add_action( 'send_headers', 'catelabo_demo_robots_header' );

// XMLサイトマップも出さない
add_filter( 'wp_sitemaps_enabled', '__return_false' );


/* =========================================================
 * フォーム冒頭にデモ注記を常時表示と、送信完了メッセージの配色
 * ======================================================= */

/**
 * フォーム冒頭にデモ注記を常時表示（No.123）
 * 全項目を入力し終えてから「送信されません」と知らせるのを避け、
 * 入力前に伝える。バリデーション自体は動作証明として残す。
 */
add_filter( 'wpcf7_form_elements', function ( $elements ) {
	$notice = '<p class="catelabo-demo-formnotice">'
		. 'このフォームはデモサイトのため、送信は行われません。'
		. '入力とエラー表示の動作はそのままお試しいただけます。'
		. '</p>';
	return $notice . $elements;
} );

/**
 * デモ注記の配色と、送信完了メッセージの緑ボーダー是正（No.123）
 * CF7は送信スキップ時も内部ステータスが sent のため緑になる。
 * デモ明示バーと同じネイビーに揃え、システム由来の告知であることを示す。
 */
add_action( 'wp_head', function () {
	?>
<style>
.catelabo-demo-formnotice{margin:0 0 1.5rem;padding:.75rem 1rem;border-left:3px solid #2C4770;background:#F2F4F8;color:#2C4770;font-size:.875rem;line-height:1.7}
.wpcf7 form.sent .wpcf7-response-output{border-color:#2C4770;background:#F2F4F8;color:#2C4770}
</style>
	<?php
}, 20 );


/* =========================================================
 * 層4: SNSアイコンのデモ表示(リンク無効)
 * 架空の猫舎として、猫舎がよく使うSNSを並べて見せる。
 * カスタマイザーの入力に関係なく、ここで決めたSNSだけを表示し、
 * どのアイコンも実在のアカウントへは移動させない(押すと案内文を表示)。
 * テーマには手を入れず theme_mod_ フィルターで差し替えるので、無効化すれば元に戻る。
 * ======================================================= */

function catelabo_demo_sns_filters() {
	if ( is_admin() || ! function_exists( 'catelabo_sns_services' ) ) {
		return;
	}
	$shown = catelabo_demo_sns_shown();
	foreach ( array_keys( catelabo_sns_services() ) as $key ) {
		$value = in_array( $key, $shown, true ) ? CATELABO_DEMO_SNS_HREF : '';
		add_filter(
			'theme_mod_catelabo_sns_' . $key,
			static function () use ( $value ) {
				return $value;
			}
		);
	}
}
add_action( 'wp', 'catelabo_demo_sns_filters' );

/** アイコンを押したら移動をやめ、案内文を短く表示する */
function catelabo_demo_sns_script() {
	?>
<style id="catelabo-demo-sns-css">
.catelabo-demo-sns-toast{position:fixed;left:50%;bottom:24px;z-index:99999;transform:translateX(-50%);max-width:calc(100% - 32px);box-sizing:border-box;padding:10px 16px;border-radius:6px;background:#2C4770;color:#fff;font-size:13px;line-height:1.6;text-align:center;opacity:0;pointer-events:none;transition:opacity .2s}
.catelabo-demo-sns-toast.is-shown{opacity:1}
@media (prefers-reduced-motion: reduce){.catelabo-demo-sns-toast{transition:none}}
</style>
<script id="catelabo-demo-sns-js">
(function () {
	var HREF = <?php echo wp_json_encode( CATELABO_DEMO_SNS_HREF ); ?>;
	var TEXT = <?php echo wp_json_encode( CATELABO_DEMO_SNS_TEXT ); ?>;
	var links = document.querySelectorAll('a[href="' + HREF + '"]');
	if (!links.length) { return; }
	var toast = document.createElement('div');
	toast.className = 'catelabo-demo-sns-toast';
	toast.setAttribute('role', 'status');
	toast.setAttribute('aria-live', 'polite');
	document.body.appendChild(toast);
	var timer;
	Array.prototype.forEach.call(links, function (a) {
		a.removeAttribute('target');
		a.addEventListener('click', function (e) {
			e.preventDefault();
			toast.textContent = TEXT;
			toast.classList.add('is-shown');
			clearTimeout(timer);
			timer = setTimeout(function () { toast.classList.remove('is-shown'); }, 3000);
		});
	});
})();
</script>
	<?php
}
add_action( 'wp_footer', 'catelabo_demo_sns_script', 20 );
