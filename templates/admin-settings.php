<?php

/**
 * Bulk AI Alt Setter 設定画面
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap baas-settings-wrap">
	<h1><?php _e( 'Bulk AI Alt Setter 設定', 'bulk-ai-alt-setter' ); ?></h1>

	<form method="post" action="">
		<?php wp_nonce_field( 'baas_settings_nonce' ); ?>
		<?php settings_errors( 'baas_settings' ); ?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="baas_api_key"><?php _e( 'APIキー', 'bulk-ai-alt-setter' ); ?></label>
				</th>
				<td>
					<input type="password"
						name="baas_api_key"
						id="baas_api_key"
						value="<?php echo esc_attr( $api_key ); ?>"
						class="regular-text" />
					<p class="description">
						<?php _e( 'OpenAI APIキーを入力してください。APIキーは <a href="https://platform.openai.com/account/api-keys" target="_blank">OpenAIのウェブサイト</a> から取得できます。', 'bulk-ai-alt-setter' ); ?>
					</p>
					<p class="description">
						<?php _e( 'このプラグインは最新のGPT-4o（gpt-4o）モデルを使用しています。', 'bulk-ai-alt-setter' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<input type="submit" name="baas_settings_submit" class="button button-primary" value="<?php _e( '設定を保存', 'bulk-ai-alt-setter' ); ?>" />
		</p>
	</form>
</div>