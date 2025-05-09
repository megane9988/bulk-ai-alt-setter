<?php
/**
 * アップロード時にAlt属性を自動設定するクラス
 */
class BAAS_Auto_Alt {
	private $api;

	/**
	 * コンストラクタ
	 */
	public function __construct() {
		$this->api = new BAAS_API();
	}

	/**
	 * 初期化処理
	 */
	public function init() {
		// 画像アップロード後にAlt属性を自動生成
		add_action( 'add_attachment', array( $this, 'auto_generate_alt_on_upload' ), 10, 1 );
	}

	/**
	 * 画像アップロード時にAlt属性を自動生成
	 *
	 * @param int $attachment_id アップロードされた添付ファイルのID
	 */
	public function auto_generate_alt_on_upload( $attachment_id ) {
		// APIキーが設定されているか確認
		if ( empty( get_option( 'baas_api_key' ) ) ) {
			return;
		}

		// 画像かどうか確認
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		// すでにAlt属性が設定されているか確認
		$existing_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		if ( ! empty( $existing_alt ) ) {
			return;
		}

		// Alt属性を生成して保存
		$this->api->generate_alt_text_and_save( $attachment_id );

		// ログに記録
		error_log( sprintf( '[Bulk AI Alt Setter] Auto-generated alt text for attachment #%d', $attachment_id ) );
	}
}
