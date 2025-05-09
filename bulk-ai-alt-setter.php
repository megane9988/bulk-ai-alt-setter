<?php
/**
 * Plugin Name: Bulk AI Alt Setter
 * Plugin URI: https://example.com/bulk-ai-alt-setter
 * Description: AIを活用してメディアライブラリの画像に一括でalt属性を設定するプラグイン
 * Version: 1.0.0
 * Author: Bulk AI Alt Setter開発チーム
 * Author URI: https://example.com
 * Text Domain: bulk-ai-alt-setter
 * Domain Path: /languages
 * License: GPL v2 or later
 */

// 直接アクセス禁止
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 定数定義
define( 'BAAS_VERSION', '1.0.0' );
define( 'BAAS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'BAAS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// 必要なファイルを読み込む
require_once BAAS_PLUGIN_DIR . 'includes/class-baas-admin.php';
require_once BAAS_PLUGIN_DIR . 'includes/class-baas-api.php';
require_once BAAS_PLUGIN_DIR . 'includes/class-baas-auto-alt.php';

/**
 * プラグインの初期化
 */
function baas_init() {
	// 管理画面の初期化
	$admin = new BAAS_Admin();
	$admin->init();

	// 自動Alt設定機能の初期化
	$auto_alt = new BAAS_Auto_Alt();
	$auto_alt->init();

	// 国際化対応
	load_plugin_textdomain( 'bulk-ai-alt-setter', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'baas_init' );

/**
 * プラグインのアクティベーション時の処理
 */
function baas_activate() {
	// アクティベーション時の処理をここに書く
	// 例: オプションの初期値設定など

	// OpenAI APIのデフォルト設定
	if ( ! get_option( 'baas_api_key' ) ) {
		add_option( 'baas_api_key', '' );
	}
}
register_activation_hook( __FILE__, 'baas_activate' );

/**
 * プラグインの停止時の処理
 */
function baas_deactivate() {
	// 停止時の処理をここに書く
}
register_deactivation_hook( __FILE__, 'baas_deactivate' );

/**
 * プラグインのアンインストール時の処理
 */
function baas_uninstall() {
	// アンインストール時の処理をここに書く
	delete_option( 'baas_api_key' );
}
register_uninstall_hook( __FILE__, 'baas_uninstall' );
