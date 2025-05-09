<?php

/**
 * 管理画面関連のクラス
 */
class BAAS_Admin {

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		// 何も処理しない
	}

	/**
	 * 初期化処理
	 */
	public function init() {
		// 管理画面メニューの追加
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

		// 管理画面用のスクリプトとスタイルの登録
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// AJAX処理の登録
		add_action( 'wp_ajax_baas_generate_alt_batch', array( $this, 'ajax_generate_alt_batch' ) );
		add_action( 'wp_ajax_baas_generate_alt_single', array( $this, 'ajax_generate_alt_single' ) );
		add_action( 'wp_ajax_baas_get_attachments', array( $this, 'ajax_get_attachments' ) );

		// メディアライブラリカラムの追加
		add_filter( 'manage_media_columns', array( $this, 'add_alt_column' ) );
		add_action( 'manage_media_custom_column', array( $this, 'display_alt_column' ), 10, 2 );

		// メディア編集画面にAIボタンを追加
		add_filter( 'attachment_fields_to_edit', array( $this, 'add_generate_button' ), 10, 2 );
	}

	/**
	 * 管理画面メニューの追加
	 */
	public function add_admin_menu() {
		// メインメニュー
		add_menu_page(
			__( 'Bulk AI Alt Setter', 'bulk-ai-alt-setter' ),
			__( 'AI Alt Setter', 'bulk-ai-alt-setter' ),
			'manage_options',
			'bulk-ai-alt-setter',
			array( $this, 'render_main_page' ),
			'dashicons-format-image',
			30
		);

		// サブメニュー：一括設定
		add_submenu_page(
			'bulk-ai-alt-setter',
			__( '一括設定', 'bulk-ai-alt-setter' ),
			__( '一括設定', 'bulk-ai-alt-setter' ),
			'manage_options',
			'bulk-ai-alt-setter',
			array( $this, 'render_main_page' )
		);

		// サブメニュー：設定
		add_submenu_page(
			'bulk-ai-alt-setter',
			__( '設定', 'bulk-ai-alt-setter' ),
			__( '設定', 'bulk-ai-alt-setter' ),
			'manage_options',
			'bulk-ai-alt-setter-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * 管理画面用のスクリプトとスタイルの登録
	 */
	public function enqueue_admin_scripts( $hook ) {
		// プラグインの管理画面だけでスクリプトをロード
		if ( strpos( $hook, 'bulk-ai-alt-setter' ) === false && $hook != 'upload.php' ) {
			return;
		}

		// スタイルの登録
		wp_enqueue_style(
			'baas-admin-style',
			BAAS_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			BAAS_VERSION
		);

		// スクリプトの登録
		wp_enqueue_script(
			'baas-admin-script',
			BAAS_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			BAAS_VERSION,
			true
		);

		// AJAX用のデータをJSに渡す
		wp_localize_script(
			'baas-admin-script',
			'baas_data',
			array(
				'ajax_url'       => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'baas_nonce' ),
				'generating_alt' => __( 'Alt属性を生成中...', 'bulk-ai-alt-setter' ),
				'success'        => __( '成功しました！', 'bulk-ai-alt-setter' ),
				'error'          => __( 'エラーが発生しました', 'bulk-ai-alt-setter' ),
			)
		);
	}

	/**
	 * メイン管理画面のレンダリング
	 */
	public function render_main_page() {
		// OpenAI APIキーが設定されているか確認
		$api_key = get_option( 'baas_api_key' );
		if ( empty( $api_key ) ) {
			echo '<div class="wrap">';
			echo '<h1>' . __( 'Bulk AI Alt Setter', 'bulk-ai-alt-setter' ) . '</h1>';
			echo '<div class="notice notice-error"><p>' .
				__( 'APIキーが設定されていません。設定ページでAPIキーを設定してください。', 'bulk-ai-alt-setter' ) .
				' <a href="' . admin_url( 'admin.php?page=bulk-ai-alt-setter-settings' ) . '">' .
				__( '設定ページへ', 'bulk-ai-alt-setter' ) . '</a></p></div>';
			echo '</div>';
			return;
		}

		// テンプレートの読み込み
		include BAAS_PLUGIN_DIR . 'templates/admin-main.php';
	}

	/**
	 * 設定ページのレンダリング
	 */
	public function render_settings_page() {
		// 設定が送信された場合の処理
		if ( isset( $_POST['baas_settings_submit'] ) && check_admin_referer( 'baas_settings_nonce' ) ) {
			// APIキーの保存
			if ( isset( $_POST['baas_api_key'] ) ) {
				update_option( 'baas_api_key', sanitize_text_field( $_POST['baas_api_key'] ) );
			}

			// 成功メッセージの表示
			add_settings_error(
				'baas_settings',
				'settings_updated',
				__( '設定が保存されました。', 'bulk-ai-alt-setter' ),
				'updated'
			);
		}

		// 現在の設定値を取得
		$api_key = get_option( 'baas_api_key' );

		// テンプレートの読み込み
		include BAAS_PLUGIN_DIR . 'templates/admin-settings.php';
	}

	/**
	 * メディアライブラリにAltタグの列を追加
	 */
	public function add_alt_column( $columns ) {
		$columns['alt_text'] = __( 'Alt属性', 'bulk-ai-alt-setter' );
		return $columns;
	}

	/**
	 * Alt属性列の内容を表示
	 */
	public function display_alt_column( $column_name, $attachment_id ) {
		if ( $column_name !== 'alt_text' ) {
			return;
		}

		$alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		echo '<div class="baas-alt-display">';
		if ( ! empty( $alt_text ) ) {
			echo esc_html( $alt_text );
		} else {
			echo '<em>' . __( '設定なし', 'bulk-ai-alt-setter' ) . '</em>';
		}
		echo ' <button type="button" class="button button-small baas-generate-single" data-id="' . esc_attr( $attachment_id ) . '">' . __( 'AI生成', 'bulk-ai-alt-setter' ) . '</button>';
		echo '</div>';
	}

	/**
	 * メディア編集画面にAI生成ボタンを追加
	 */
	public function add_generate_button( $form_fields, $post ) {
		$form_fields['baas_generate_alt'] = array(
			'label' => __( 'AI Alt生成', 'bulk-ai-alt-setter' ),
			'input' => 'html',
			'html'  => '<button type="button" class="button baas-generate-single" data-id="' . esc_attr( $post->ID ) . '">' . __( 'AIでAlt属性を生成', 'bulk-ai-alt-setter' ) . '</button>',
		);

		return $form_fields;
	}

	/**
	 * 単一画像のAlt属性生成処理（AJAX）
	 */
	public function ajax_generate_alt_single() {
		// セキュリティチェック
		check_ajax_referer( 'baas_nonce', 'nonce' );

		// パラメータチェック
		if ( ! isset( $_POST['attachment_id'] ) || ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'パラメータが不正です。', 'bulk-ai-alt-setter' ),
				)
			);
		}

		$attachment_id = intval( $_POST['attachment_id'] );

		// APIクラスのインスタンス化
		$api = new BAAS_API();

		// Alt属性生成
		$alt_text = $api->generate_alt_text( $attachment_id );

		if ( is_wp_error( $alt_text ) ) {
			wp_send_json_error(
				array(
					'message' => $alt_text->get_error_message(),
				)
			);
		}

		// Alt属性を保存
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );

		wp_send_json_success(
			array(
				'message'  => __( 'Alt属性が生成されました。', 'bulk-ai-alt-setter' ),
				'alt_text' => $alt_text,
			)
		);
	}

	/**
	 * 複数画像のAlt属性一括生成処理（AJAX）
	 */
	public function ajax_generate_alt_batch() {
		// セキュリティチェック
		check_ajax_referer( 'baas_nonce', 'nonce' );

		// パラメータチェック
		if ( ! isset( $_POST['attachment_ids'] ) || ! current_user_can( 'upload_files' ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'パラメータが不正です。', 'bulk-ai-alt-setter' ),
				)
			);
		}

		$attachment_ids = $_POST['attachment_ids'];
		$results        = array();
		$success_count  = 0;
		$error_count    = 0;

		// APIクラスのインスタンス化
		$api = new BAAS_API();

		foreach ( $attachment_ids as $attachment_id ) {
			$attachment_id = intval( $attachment_id );

			// すでにAlt属性が設定されている場合はスキップ（オプションの設定によって変更可能）
			$skip_existing = isset( $_POST['skip_existing'] ) && $_POST['skip_existing'] === 'true';
			if ( $skip_existing ) {
				$existing_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
				if ( ! empty( $existing_alt ) ) {
					$results[] = array(
						'id'      => $attachment_id,
						'status'  => 'skipped',
						'message' => __( 'すでにAlt属性が設定されています。', 'bulk-ai-alt-setter' ),
					);
					continue;
				}
			}

			// Alt属性生成
			$alt_text = $api->generate_alt_text( $attachment_id );

			if ( is_wp_error( $alt_text ) ) {
				$results[] = array(
					'id'      => $attachment_id,
					'status'  => 'error',
					'message' => $alt_text->get_error_message(),
				);
				++$error_count;
			} else {
				// Alt属性を保存
				update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );

				$results[] = array(
					'id'       => $attachment_id,
					'status'   => 'success',
					'alt_text' => $alt_text,
				);
				++$success_count;
			}
		}

		wp_send_json_success(
			array(
				'message'       => sprintf(
					__( '%1$d個の画像にAlt属性が生成されました。%2$d個の画像でエラーが発生しました。', 'bulk-ai-alt-setter' ),
					$success_count,
					$error_count
				),
				'results'       => $results,
				'success_count' => $success_count,
				'error_count'   => $error_count,
			)
		);
	}

	/**
	 * 処理対象の画像一覧を取得するAJAX処理
	 */
	public function ajax_get_attachments() {
		// セキュリティチェック
		check_ajax_referer( 'baas_nonce', 'nonce' );

		// パラメータの取得と検証
		$skip_existing = isset( $_GET['skip_existing'] ) && $_GET['skip_existing'] === 'true';
		$only_images   = isset( $_GET['only_images'] ) && $_GET['only_images'] === 'true';
		$limit         = isset( $_GET['limit'] ) ? intval( $_GET['limit'] ) : 50;

		// クエリ引数の準備
		$args = array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => $limit > 0 ? $limit : 100, // 最大100件まで
		);

		// 画像ファイルのみを対象とする場合
		if ( $only_images ) {
			$args['post_mime_type'] = array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' );
		}

		// クエリの実行
		$attachments_query = new WP_Query( $args );
		$attachments       = array();

		if ( $attachments_query->have_posts() ) {
			foreach ( $attachments_query->posts as $attachment ) {
				// すでにAlt属性が設定されている場合はスキップ
				if ( $skip_existing ) {
					$alt_text = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );
					if ( ! empty( $alt_text ) ) {
						continue;
					}
				}

				// サムネイル画像のURLを取得
				$thumb     = wp_get_attachment_image_src( $attachment->ID, 'thumbnail' );
				$thumb_url = $thumb ? $thumb[0] : '';

				// 画像情報の収集
				$attachments[] = array(
					'id'        => $attachment->ID,
					'title'     => $attachment->post_title,
					'filename'  => basename( get_attached_file( $attachment->ID ) ),
					'thumbnail' => $thumb_url,
					'url'       => wp_get_attachment_url( $attachment->ID ),
				);
			}
		}

		wp_send_json_success(
			array(
				'attachments' => $attachments,
				'total'       => count( $attachments ),
			)
		);
	}
}
