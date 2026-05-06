<?php
    $defaults = is_array($defaults ?? null) ? $defaults : [];
    $storedTexts = is_array($storedTexts ?? null) ? $storedTexts : [];
    $effectiveTexts = is_array($effectiveTexts ?? null) ? $effectiveTexts : [];
    $text = static fn (mixed $value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
    $fieldGroups = [
        'Front Panel' => [
            'print_card_brand_display_name' => 'Brand display name',
            'print_card_brand_subtitle' => 'Small subtitle',
            'print_card_front_kicker' => 'Front panel label',
        ],
        'Center Message Panel' => [
            'print_card_center_heading' => 'Message heading',
            'print_card_empty_message_fallback' => 'Empty message fallback',
        ],
        'Details Panel' => [
            'print_card_details_heading' => 'Details title',
            'print_card_label_product' => 'Product label',
            'print_card_label_size' => 'Size label',
            'print_card_label_recipient' => 'Recipient label',
            'print_card_label_delivery_date' => 'Delivery date label',
            'print_card_label_store_contact' => 'Store contact label',
        ],
    ];
?>

<div class="admin-form-shell">
    <div class="admin-topbar" style="margin-bottom:0;">
        <div>
            <p class="admin-kicker">Print Materials</p>
            <h2 class="admin-title">Print Card Note Text</h2>
            <p class="admin-subtitle">Customize the words used on the folded A4 gift message sheet. Leave a field blank to use its default.</p>
        </div>
        <a href="/admin/site-settings" class="admin-button-secondary">Back to Site Settings</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="admin-alert error"><?php echo $text($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="admin-alert success"><?php echo $text($success); ?></div>
    <?php endif; ?>

    <div class="print-card-settings-layout">
        <form method="post" action="/admin/site-settings/card-note" class="admin-card print-card-settings-form" data-print-card-settings-form>
            <?php echo csrf_field(); ?>

            <div class="order-card-heading">
                <div>
                    <p class="admin-kicker">Editable Text</p>
                    <h3 class="order-card-title">Card note wording</h3>
                </div>
                <button type="submit" class="admin-button-secondary" name="reset_all" value="1" data-print-card-reset-all>Reset All To Defaults</button>
            </div>

            <?php foreach ($fieldGroups as $groupTitle => $fields): ?>
                <section class="print-card-settings-group">
                    <h4><?php echo $text($groupTitle); ?></h4>
                    <div class="print-card-settings-fields">
                        <?php foreach ($fields as $key => $label): ?>
                            <?php
                                $default = (string) ($defaults[$key] ?? '');
                                $stored = (string) ($storedTexts[$key] ?? '');
                                $effective = (string) ($effectiveTexts[$key] ?? $default);
                                $isCustom = $stored !== '' && $stored !== $default;
                            ?>
                            <div class="print-card-setting-field<?php echo $isCustom ? ' is-custom' : ''; ?>" data-print-card-field="<?php echo $text($key); ?>" data-default-value="<?php echo $text($default); ?>">
                                <div class="print-card-setting-field__head">
                                    <label for="<?php echo $text($key); ?>"><?php echo $text($label); ?></label>
                                    <span class="print-card-setting-badge" data-print-card-badge><?php echo $isCustom ? 'Custom' : 'Using default'; ?></span>
                                </div>
                                <?php if (in_array($key, ['print_card_brand_subtitle', 'print_card_empty_message_fallback'], true)): ?>
                                    <textarea id="<?php echo $text($key); ?>" name="<?php echo $text($key); ?>" rows="2" placeholder="<?php echo $text($default); ?>" data-print-card-input><?php echo $text($stored); ?></textarea>
                                <?php else: ?>
                                    <input id="<?php echo $text($key); ?>" name="<?php echo $text($key); ?>" type="text" value="<?php echo $text($stored); ?>" placeholder="<?php echo $text($default); ?>" data-print-card-input>
                                <?php endif; ?>
                                <input type="hidden" name="reset_field[<?php echo $text($key); ?>]" value="0" data-print-card-reset-flag>
                                <div class="print-card-setting-field__foot">
                                    <span>Default: <?php echo $text($default !== '' ? $default : 'blank'); ?></span>
                                    <button type="button" class="admin-text-button" data-print-card-reset-one>Reset this field</button>
                                </div>
                                <?php if ($stored === ''): ?>
                                    <p class="admin-note">Current print text: <?php echo $text($effective); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endforeach; ?>

            <div class="print-card-settings-actions">
                <button type="submit" class="admin-button">Save Print Card Text</button>
                <button type="button" class="admin-button-secondary" data-print-card-preview>Refresh Preview</button>
            </div>
        </form>

        <aside class="admin-card print-card-preview-panel">
            <div class="order-card-heading">
                <div>
                    <p class="admin-kicker">Live Preview</p>
                    <h3 class="order-card-title">Sample folded sheet</h3>
                </div>
                <a href="/admin/site-settings/card-note-preview" class="admin-text-button" target="_blank" rel="noopener" data-print-card-preview-link>Open Preview</a>
            </div>
            <iframe title="Print card note preview" src="/admin/site-settings/card-note-preview" data-print-card-preview-frame></iframe>
            <p class="admin-note" data-print-card-preview-status>Live preview updates as you type. Preview uses sample order data; save changes to apply them to real order print sheets.</p>
        </aside>
    </div>
</div>

<script>
    (function () {
        var form = document.querySelector('[data-print-card-settings-form]');
        if (!form) {
            return;
        }

        var previewTimer = null;

        function fieldValue(field) {
            var input = field.querySelector('[data-print-card-input]');
            return input ? input.value.trim() : '';
        }

        function defaultValue(field) {
            return field.getAttribute('data-default-value') || '';
        }

        function syncField(field) {
            var input = field.querySelector('[data-print-card-input]');
            var badge = field.querySelector('[data-print-card-badge]');
            var resetFlag = field.querySelector('[data-print-card-reset-flag]');
            var isCustom = fieldValue(field) !== '' && fieldValue(field) !== defaultValue(field);

            field.classList.toggle('is-custom', isCustom);

            if (badge) {
                badge.textContent = isCustom ? 'Custom' : 'Using default';
            }

            if (resetFlag && input && input.value.trim() !== '') {
                resetFlag.value = '0';
            }
        }

        function buildPreviewUrl() {
            var params = new URLSearchParams();
            form.querySelectorAll('[data-print-card-field]').forEach(function (field) {
                var key = field.getAttribute('data-print-card-field') || '';
                var value = fieldValue(field);
                if (key !== '') {
                    params.append('preview[' + key + ']', value);
                }
            });

            return '/admin/site-settings/card-note-preview?' + params.toString();
        }

        function updatePreview() {
            var url = buildPreviewUrl();
            var previewFrame = document.querySelector('[data-print-card-preview-frame]');
            var previewLink = document.querySelector('[data-print-card-preview-link]');
            var previewStatus = document.querySelector('[data-print-card-preview-status]');

            if (previewFrame) {
                previewFrame.src = url;
            }

            if (previewLink) {
                previewLink.href = url;
            }

            if (previewStatus) {
                previewStatus.textContent = 'Preview updated. Save changes to apply them to real order print sheets.';
            }
        }

        function queuePreviewUpdate() {
            window.clearTimeout(previewTimer);
            previewTimer = window.setTimeout(updatePreview, 350);
        }

        form.querySelectorAll('[data-print-card-field]').forEach(function (field) {
            var input = field.querySelector('[data-print-card-input]');
            var resetButton = field.querySelector('[data-print-card-reset-one]');
            var resetFlag = field.querySelector('[data-print-card-reset-flag]');

            if (input) {
                input.addEventListener('input', function () {
                    syncField(field);
                    queuePreviewUpdate();
                });

                input.addEventListener('change', function () {
                    syncField(field);
                    queuePreviewUpdate();
                });
            }

            if (resetButton) {
                resetButton.addEventListener('click', function () {
                    if (input) {
                        input.value = '';
                    }

                    if (resetFlag) {
                        resetFlag.value = '1';
                    }

                    syncField(field);
                    updatePreview();
                });
            }
        });

        var previewButton = form.querySelector('[data-print-card-preview]');
        var previewFrame = document.querySelector('[data-print-card-preview-frame]');
        var previewLink = document.querySelector('[data-print-card-preview-link]');

        if (previewButton) {
            previewButton.addEventListener('click', function () {
                updatePreview();
            });
        }
    })();
</script>
