/**
 * Bulk AI Alt Setter 管理画面用JavaScript
 */
(function($) {
    'use strict';
    
    // 一括処理の状態管理
    const batchProcess = {
        attachments: [],
        processedCount: 0,
        successCount: 0,
        errorCount: 0,
        skipCount: 0,
        inProgress: false,
        results: []
    };
    
    /**
     * 初期化処理
     */
    function init() {
        // プレビューボタンのクリックイベント
        $('#baas-preview-button').on('click', handlePreviewClick);
        
        // 生成ボタンのクリックイベント
        $('#baas-generate-button').on('click', handleGenerateClick);
        
        // 単一画像の生成ボタンのクリックイベント（メディアライブラリ）
        $(document).on('click', '.baas-generate-single', handleSingleGenerateClick);
    }
    
    /**
     * プレビューボタンのクリックハンドラー
     */
    function handlePreviewClick() {
        const options = getFormOptions();
        
        // プレビュー表示をクリア
        $('#baas-preview-images').empty();
        $('#baas-preview').hide();
        
        // 対象の画像を取得
        $.ajax({
            url: baas_data.ajax_url,
            type: 'GET',
            data: {
                action: 'baas_get_attachments',
                nonce: baas_data.nonce,
                skip_existing: options.skipExisting,
                only_images: options.onlyImages,
                limit: options.limit
            },
            beforeSend: function() {
                showLoading($('#baas-preview-button'));
            },
            success: function(response) {
                if (response.success) {
                    batchProcess.attachments = response.data.attachments;
                    displayPreview(response.data.attachments);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError(baas_data.error);
            },
            complete: function() {
                hideLoading($('#baas-preview-button'));
            }
        });
    }
    
    /**
     * 一括生成ボタンのクリックハンドラー
     */
    function handleGenerateClick() {
        const options = getFormOptions();
        
        if (batchProcess.inProgress) {
            return;
        }
        
        // プレビューが行われていない場合は、まずプレビューを取得
        if (batchProcess.attachments.length === 0) {
            $.ajax({
                url: baas_data.ajax_url,
                type: 'GET',
                data: {
                    action: 'baas_get_attachments',
                    nonce: baas_data.nonce,
                    skip_existing: options.skipExisting,
                    only_images: options.onlyImages,
                    limit: options.limit
                },
                beforeSend: function() {
                    showLoading($('#baas-generate-button'));
                },
                success: function(response) {
                    if (response.success) {
                        batchProcess.attachments = response.data.attachments;
                        startBatchProcess(options);
                    } else {
                        showError(response.data.message);
                        hideLoading($('#baas-generate-button'));
                    }
                },
                error: function() {
                    showError(baas_data.error);
                    hideLoading($('#baas-generate-button'));
                }
            });
        } else {
            startBatchProcess(options);
        }
    }
    
    /**
     * 単一画像のAlt生成ボタンのクリックハンドラー
     */
    function handleSingleGenerateClick(e) {
        e.preventDefault();
        
        const $button = $(this);
        const attachmentId = $button.data('id');
        
        if (!attachmentId) {
            return;
        }
        
        $.ajax({
            url: baas_data.ajax_url,
            type: 'POST',
            data: {
                action: 'baas_generate_alt_single',
                nonce: baas_data.nonce,
                attachment_id: attachmentId
            },
            beforeSend: function() {
                $button.prop('disabled', true).text(baas_data.generating_alt);
            },
            success: function(response) {
                if (response.success) {
                    // 成功メッセージを表示
                    const $message = $('<span class="baas-success-message">' + response.data.alt_text + '</span>');
                    
                    // Alt表示を更新
                    if ($button.closest('.baas-alt-display').length) {
                        $button.closest('.baas-alt-display').find('em').remove();
                        $button.closest('.baas-alt-display').prepend(response.data.alt_text + ' ');
                    }
                    
                    // メディア編集画面の場合はalt入力欄を更新
                    if ($('#attachment_alt').length) {
                        $('#attachment_alt').val(response.data.alt_text);
                    }
                    
                    // 成功メッセージを一時的に表示
                    $button.after($message);
                    setTimeout(function() {
                        $message.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 3000);
                } else {
                    showError(response.data.message);
                }
            },
            error: function() {
                showError(baas_data.error);
            },
            complete: function() {
                $button.prop('disabled', false).text('AI生成');
            }
        });
    }
    
    /**
     * フォームのオプション値を取得
     */
    function getFormOptions() {
        return {
            skipExisting: $('#baas-batch-form input[name="skip_existing"]').prop('checked'),
            onlyImages: $('#baas-batch-form input[name="only_images"]').prop('checked'),
            limit: parseInt($('#baas-batch-form select[name="limit"]').val(), 10)
        };
    }
    
    /**
     * 画像のプレビューを表示
     */
    function displayPreview(attachments) {
        const $previewCount = $('#baas-preview-count');
        const $previewImages = $('#baas-preview-images');
        
        $previewCount.html('<p>' + attachments.length + '枚の画像が処理対象です</p>');
        
        if (attachments.length === 0) {
            $previewImages.html('<p>条件に一致する画像がありません。</p>');
            $('#baas-preview').show();
            return;
        }
        
        // プレビュー画像のHTML生成
        attachments.forEach(function(attachment) {
            const $imageBox = $('<div class="baas-image-box"></div>');
            $imageBox.append('<img src="' + attachment.thumbnail + '" alt="' + attachment.filename + '">');
            $imageBox.append('<div class="baas-image-info">' + attachment.filename + '</div>');
            $previewImages.append($imageBox);
        });
        
        $('#baas-preview').show();
    }
    
    /**
     * 一括処理の開始
     */
    function startBatchProcess(options) {
        if (batchProcess.attachments.length === 0) {
            showError('処理対象の画像がありません。');
            return;
        }
        
        // 処理状態をリセット
        batchProcess.processedCount = 0;
        batchProcess.successCount = 0;
        batchProcess.errorCount = 0;
        batchProcess.skipCount = 0;
        batchProcess.inProgress = true;
        batchProcess.results = [];
        
        // UI表示
        $('#baas-progress').show();
        $('#baas-results').hide();
        updateProgress(0, batchProcess.attachments.length);
        
        // 一括処理の実行（10枚ずつ処理）
        processBatch(options);
    }
    
    /**
     * 画像のバッチ処理
     */
    function processBatch(options) {
        const batchSize = 10; // 一度に処理する画像数
        const totalImages = batchProcess.attachments.length;
        const startIndex = batchProcess.processedCount;
        const endIndex = Math.min(startIndex + batchSize, totalImages);
        
        if (startIndex >= totalImages) {
            // すべての処理が完了
            finishBatchProcess();
            return;
        }
        
        // 現在のバッチの画像IDリスト
        const currentBatch = batchProcess.attachments.slice(startIndex, endIndex).map(item => item.id);
        
        $.ajax({
            url: baas_data.ajax_url,
            type: 'POST',
            data: {
                action: 'baas_generate_alt_batch',
                nonce: baas_data.nonce,
                attachment_ids: currentBatch,
                skip_existing: options.skipExisting
            },
            success: function(response) {
                if (response.success) {
                    batchProcess.processedCount += currentBatch.length;
                    batchProcess.successCount += response.data.success_count;
                    batchProcess.errorCount += response.data.error_count;
                    batchProcess.results = batchProcess.results.concat(response.data.results);
                    
                    // 進捗表示の更新
                    updateProgress(batchProcess.processedCount, totalImages);
                    
                    // 次のバッチを処理
                    processBatch(options);
                } else {
                    showError(response.data.message);
                    batchProcess.inProgress = false;
                    hideLoading($('#baas-generate-button'));
                }
            },
            error: function() {
                showError(baas_data.error);
                batchProcess.inProgress = false;
                hideLoading($('#baas-generate-button'));
            }
        });
    }
    
    /**
     * 進捗表示の更新
     */
    function updateProgress(processedCount, totalCount) {
        const percent = Math.floor((processedCount / totalCount) * 100);
        $('#baas-progress-inner').css('width', percent + '%');
        $('#baas-progress-text').text(processedCount + ' / ' + totalCount + ' 処理中... (' + percent + '%)');
    }
    
    /**
     * 一括処理の完了
     */
    function finishBatchProcess() {
        batchProcess.inProgress = false;
        hideLoading($('#baas-generate-button'));
        
        // 結果サマリーの表示
        const $resultsSummary = $('#baas-results-summary');
        $resultsSummary.html(
            '<p>処理が完了しました。合計: ' + batchProcess.attachments.length + 
            '枚、成功: ' + batchProcess.successCount + 
            '枚、エラー: ' + batchProcess.errorCount + 
            '枚、スキップ: ' + batchProcess.skipCount + '枚</p>'
        );
        
        // 詳細結果の表示
        const $resultsDetails = $('#baas-results-details');
        $resultsDetails.empty();
        
        if (batchProcess.results.length > 0) {
            const $table = $('<table class="wp-list-table widefat striped"></table>');
            const $thead = $('<thead><tr><th>画像</th><th>ステータス</th><th>生成されたAlt属性</th></tr></thead>');
            const $tbody = $('<tbody></tbody>');
            
            batchProcess.results.forEach(function(result) {
                const attachment = batchProcess.attachments.find(a => a.id === result.id);
                const $row = $('<tr></tr>');
                
                // 画像セル
                const $imgCell = $('<td></td>');
                if (attachment) {
                    $imgCell.append('<img src="' + attachment.thumbnail + '" width="50" height="50" style="object-fit: cover;">');
                    $imgCell.append('<div>' + attachment.filename + '</div>');
                }
                
                // ステータスセル
                const $statusCell = $('<td></td>');
                if (result.status === 'success') {
                    $statusCell.append('<span class="baas-success">成功</span>');
                } else if (result.status === 'error') {
                    $statusCell.append('<span class="baas-error">エラー</span>');
                    $statusCell.append('<div class="baas-error-message">' + result.message + '</div>');
                } else if (result.status === 'skipped') {
                    $statusCell.append('<span class="baas-skipped">スキップ</span>');
                    $statusCell.append('<div>' + result.message + '</div>');
                }
                
                // Alt属性セル
                const $altCell = $('<td></td>');
                if (result.alt_text) {
                    $altCell.text(result.alt_text);
                }
                
                $row.append($imgCell, $statusCell, $altCell);
                $tbody.append($row);
            });
            
            $table.append($thead, $tbody);
            $resultsDetails.append($table);
        }
        
        $('#baas-results').show();
    }
    
    /**
     * エラーメッセージの表示
     */
    function showError(message) {
        const $errorNotice = $('<div class="notice notice-error is-dismissible"><p>' + message + '</p></div>');
        $('.baas-main-wrap h1').after($errorNotice);
        
        // WordPressの通知を初期化（閉じるボタンを動作させるため）
        if (typeof wp !== 'undefined' && wp.notices && wp.notices.notice) {
            wp.notices.notice.init();
        }
    }
    
    /**
     * ローディング表示
     */
    function showLoading($button) {
        $button.prop('disabled', true).addClass('updating-message');
    }
    
    /**
     * ローディング非表示
     */
    function hideLoading($button) {
        $button.prop('disabled', false).removeClass('updating-message');
    }
    
    // 初期化処理の実行
    $(document).ready(init);
    
})(jQuery);
