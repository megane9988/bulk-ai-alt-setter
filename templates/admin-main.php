<?php

/**
 * Bulk AI Alt Setter メイン画面
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap baas-main-wrap">
	<h1><?php _e( 'Bulk AI Alt Setter - 一括Alt設定', 'bulk-ai-alt-setter' ); ?></h1>

	<div class="baas-intro">
		<p><?php _e( 'このツールを使用して、メディアライブラリの画像にAIを使って一括でAlt属性を設定できます。', 'bulk-ai-alt-setter' ); ?></p>
	</div>

	<div class="baas-options">
		<form id="baas-batch-form">
			<h2><?php _e( 'Alt属性設定オプション', 'bulk-ai-alt-setter' ); ?></h2>

			<div class="baas-option-row">
				<label>
					<input type="checkbox" name="skip_existing" value="1" checked>
					<?php _e( '既存のAlt属性を持つ画像をスキップする', 'bulk-ai-alt-setter' ); ?>
				</label>
			</div>

			<div class="baas-option-row">
				<label>
					<input type="checkbox" name="only_images" value="1" checked>
					<?php _e( '画像ファイルのみを処理する（JPG, PNG, GIF, WebP）', 'bulk-ai-alt-setter' ); ?>
				</label>
			</div>

			<div class="baas-option-row">
				<label for="baas-limit"><?php _e( '一度に処理する最大画像数:', 'bulk-ai-alt-setter' ); ?></label>
				<select name="limit" id="baas-limit">
					<option value="1">1</option>
					<option value="10">10</option>
					<option value="25">25</option>
					<option value="50" selected>50</option>
					<option value="100">100</option>
					<option value="0"><?php _e( 'すべて', 'bulk-ai-alt-setter' ); ?></option>
				</select>
				<p class="description"><?php _e( '大量の画像を処理する場合は、少ない数から始めることをお勧めします。', 'bulk-ai-alt-setter' ); ?></p>
			</div>

			<div class="baas-button-row">
				<button type="button" id="baas-preview-button" class="button button-secondary"><?php _e( '対象画像をプレビュー', 'bulk-ai-alt-setter' ); ?></button>
				<button type="button" id="baas-generate-button" class="button button-primary"><?php _e( 'Alt属性を一括生成', 'bulk-ai-alt-setter' ); ?></button>
			</div>
		</form>
	</div>

	<div id="baas-preview" class="baas-preview" style="display:none;">
		<h2><?php _e( '処理対象画像のプレビュー', 'bulk-ai-alt-setter' ); ?></h2>
		<div id="baas-preview-count"></div>
		<div id="baas-preview-images" class="baas-image-grid"></div>
	</div>

	<div id="baas-progress" class="baas-progress" style="display:none;">
		<h2><?php _e( '処理進捗状況', 'bulk-ai-alt-setter' ); ?></h2>
		<div class="baas-progress-bar">
			<div id="baas-progress-inner" class="baas-progress-inner"></div>
		</div>
		<div id="baas-progress-text" class="baas-progress-text"></div>
	</div>

	<div id="baas-results" class="baas-results" style="display:none;">
		<h2><?php _e( '処理結果', 'bulk-ai-alt-setter' ); ?></h2>
		<div id="baas-results-summary" class="baas-results-summary"></div>
		<div id="baas-results-details" class="baas-results-details"></div>
	</div>
</div>