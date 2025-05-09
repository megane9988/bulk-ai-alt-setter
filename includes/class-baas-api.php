<?php
/**
 * OpenAI Vision API を使って ALT 属性を生成・保存する専用クラス
 */
class BAAS_API {
	private $api_endpoint = 'https://api.openai.com/v1/chat/completions';
	private $api_key;
	private $model = 'gpt-4o';

	public function __construct() {
		$this->api_key = get_option( 'baas_api_key' );
	}

	/**
	 * ALTテキストを生成して保存
	 */
	public function generate_alt_text_and_save( $attachment_id ) {
		$alt_text = $this->generate_alt_text( $attachment_id );
		if ( is_wp_error( $alt_text ) ) {
			return $alt_text;
		}
		update_post_meta( $attachment_id, '_wp_attachment_image_alt', $alt_text );
		return $alt_text;
	}

	/**
	 * ALTテキストを生成
	 */
	public function generate_alt_text( $attachment_id ) {
		$attachment  = get_post( $attachment_id );
		$image_url   = wp_get_attachment_url( $attachment_id );
		$filename    = basename( $image_url );
		$description = ! empty( $attachment->post_content ) ? $attachment->post_content : '';

		// ローカル環境の場合は画像データを直接Base64エンコードして送信
		if ( ! filter_var( $image_url, FILTER_VALIDATE_URL ) || strpos( $image_url, 'localhost' ) !== false || strpos( $image_url, '.local' ) !== false ) {
			// 画像ファイルのパスを取得
			$image_path = get_attached_file( $attachment_id );

			// 画像ファイルが存在するか確認
			if ( ! file_exists( $image_path ) ) {
				return new WP_Error( 'invalid_image_path', __( '画像ファイルが見つかりません。', 'bulk-ai-alt-setter' ) );
			}

			// 画像をBase64エンコード
			$image_data = base64_encode( file_get_contents( $image_path ) );
			$mime_type  = get_post_mime_type( $attachment_id );

			// Base64形式のデータURIを作成
			$base64_image = "data:{$mime_type};base64,{$image_data}";

			// Base64形式の画像でプロンプトを生成
			$prompt = $this->build_vision_prompt_base64( $base64_image, $filename, $description );
		} else {
			// 通常のURL形式の画像でプロンプトを生成
			$prompt = $this->build_vision_prompt( $image_url, $filename, $description );
		}

		$response = $this->request_to_openai( $prompt );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		return $this->parse_alt_response( $response );
	}

	/**
	 * Vision用プロンプトを生成
	 */
	private function build_vision_prompt( $image_url, $filename, $description = '' ) {
		$text = "この画像に適したalt属性（SEOとアクセシビリティ両方考慮）を日本語で教えてください。画像ファイル名: {$filename}";
		if ( ! empty( $description ) ) {
			$text .= "\n画像の説明: {$description}";
		}
		$text .= "\n\n回答は短く簡潔な文章で、alt属性のテキストのみを返してください。例: \"白いテーブルに置かれた赤いバラ\"";

		return array(
			array(
				'role'    => 'user',
				'content' => array(
					array(
						'type' => 'text',
						'text' => $text,
					),
					array(
						'type'      => 'image_url',
						'image_url' => array( 'url' => $image_url ),
					),
				),
			),
		);
	}

	/**
	 * Base64画像用プロンプトを生成
	 */
	private function build_vision_prompt_base64( $base64_image, $filename, $description = '' ) {
		$text = "この画像に適したalt属性（SEOとアクセシビリティ両方考慮）を日本語で教えてください。画像ファイル名: {$filename}";
		if ( ! empty( $description ) ) {
			$text .= "\n画像の説明: {$description}";
		}
		$text .= "\n\n回答は短く簡潔な文章で、alt属性のテキストのみを返してください。例: \"白いテーブルに置かれた赤いバラ\"";

		return array(
			array(
				'role'    => 'user',
				'content' => array(
					array(
						'type' => 'text',
						'text' => $text,
					),
					array(
						'type'      => 'image_url',
						'image_url' => array( 'url' => $base64_image ),
					),
				),
			),
		);
	}

	/**
	 * OpenAIへリクエスト送信
	 */
	private function request_to_openai( $messages ) {
		if ( empty( $this->api_key ) ) {
			return new WP_Error( 'no_api_key', __( 'APIキーが設定されていません。', 'bulk-ai-alt-setter' ) );
		}

		$request_data = array(
			'model'       => $this->model,
			'messages'    => $messages,
			'temperature' => 0.7,
			'max_tokens'  => 150,
		);

		$response = wp_remote_post(
			$this->api_endpoint,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $this->api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode( $request_data ),
				'timeout' => 60,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			$msg  = isset( $body['error']['message'] ) ? $body['error']['message'] : __( 'APIリクエストに失敗しました。', 'bulk-ai-alt-setter' );
			return new WP_Error( 'api_error', $msg . " ({$code})" );
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * ALTテキストの抽出
	 */
	private function parse_alt_response( $response ) {
		$data = json_decode( $response, true );
		$text = $data['choices'][0]['message']['content'] ?? null;
		if ( ! $text ) {
			return __( 'レスポンスの解析に失敗しました。', 'bulk-ai-alt-setter' );
		}
		return trim( trim( $text ), '"\'' );
	}
}
