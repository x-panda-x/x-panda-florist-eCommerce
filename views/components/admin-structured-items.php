<?php

declare(strict_types=1);

if (!function_exists('admin_structured_h')) {
    function admin_structured_h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('admin_structured_parse_items')) {
    /**
     * @return array<int, array{title: string, body: string, url: string}>
     */
    function admin_structured_parse_items(string $value, bool $allowBody, bool $allowUrl): array
    {
        $items = [];
        $lines = preg_split('/\r\n|\r|\n/', $value) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $title = $line;
            $body = '';
            $url = '';

            if ($allowBody && $allowUrl) {
                $parts = array_map('trim', explode('|', $line));
                $title = $parts[0] ?? '';

                if (count($parts) >= 3) {
                    $body = $parts[1] ?? '';
                    $url = $parts[2] ?? '';
                } elseif (count($parts) === 2) {
                    if (str_starts_with($parts[1], '/') || filter_var($parts[1], FILTER_VALIDATE_URL) !== false) {
                        $url = $parts[1];
                    } else {
                        $body = $parts[1];
                    }
                }
            } elseif ($allowUrl && str_contains($line, '|')) {
                [$titlePart, $urlPart] = array_pad(explode('|', $line, 2), 2, '');
                $title = trim($titlePart);
                $url = trim($urlPart);
            } elseif ($allowBody && str_contains($line, '|')) {
                [$titlePart, $bodyPart] = array_pad(explode('|', $line, 2), 2, '');
                $title = trim($titlePart);
                $body = trim($bodyPart);
            }

            $title = trim($title);

            if ($title === '') {
                continue;
            }

            $items[] = [
                'title' => $title,
                'body' => trim($body),
                'url' => trim($url),
            ];
        }

        return $items;
    }
}

if (!function_exists('admin_structured_url_known')) {
    /**
     * @param array<int, array<string, mixed>> $groups
     */
    function admin_structured_url_known(array $groups, string $value): bool
    {
        $value = trim($value);

        if ($value === '') {
            return true;
        }

        foreach ($groups as $group) {
            foreach (($group['options'] ?? []) as $option) {
                if ((string) ($option['value'] ?? '') === $value) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('admin_structured_render_row_controls')) {
    function admin_structured_render_row_controls(): void
    {
        ?>
        <div class="admin-structured-items__controls">
            <button type="button" class="admin-button-secondary" data-structured-move-up>Up</button>
            <button type="button" class="admin-button-secondary" data-structured-move-down>Down</button>
            <button type="button" class="admin-text-button" style="color:#8b3c39;" data-structured-remove-row>Remove</button>
        </div>
        <?php
    }
}

if (!function_exists('admin_structured_render_items_editor')) {
    /**
     * @param array{
     *   id: string,
     *   name: string,
     *   label?: string,
     *   value?: string,
     *   note?: string,
     *   add_label?: string,
     *   allow_body?: bool,
     *   allow_url?: bool,
     *   link_options?: array<int, array<string, mixed>>
     * } $args
     */
    function admin_structured_render_items_editor(array $args): void
    {
        $id = (string) ($args['id'] ?? '');
        $name = (string) ($args['name'] ?? '');
        $label = (string) ($args['label'] ?? 'Items');
        $value = (string) ($args['value'] ?? '');
        $note = (string) ($args['note'] ?? '');
        $addLabel = (string) ($args['add_label'] ?? 'Add Item');
        $allowBody = !empty($args['allow_body']);
        $allowUrl = !empty($args['allow_url']);
        $linkOptions = is_array($args['link_options'] ?? null) ? $args['link_options'] : [];
        $items = admin_structured_parse_items($value, $allowBody, $allowUrl);

        ?>
        <div
            class="admin-field admin-structured-items"
            data-structured-editor
            data-allow-body="<?php echo $allowBody ? '1' : '0'; ?>"
            data-allow-url="<?php echo $allowUrl ? '1' : '0'; ?>"
        >
            <label><?php echo admin_structured_h($label); ?></label>
            <div class="admin-note admin-structured-items__summary" data-structured-summary>
                <?php if ($items === []): ?>
                    No saved items yet.
                <?php else: ?>
                    <?php echo admin_structured_h((string) count($items)); ?> saved item(s).
                <?php endif; ?>
            </div>
            <input type="hidden" id="<?php echo admin_structured_h($id); ?>" name="<?php echo admin_structured_h($name); ?>" value="<?php echo admin_structured_h($value); ?>" data-structured-output>
            <div class="admin-structured-items__rows" data-structured-rows>
                <?php foreach ($items as $index => $item): ?>
                    <?php $rowId = $id . '_row_' . $index; ?>
                    <div class="admin-card admin-soft-card admin-structured-items__row" data-structured-row>
                        <div class="admin-grid cols-2">
                            <div class="admin-field">
                                <label>Item Title</label>
                                <input type="text" data-structured-title value="<?php echo admin_structured_h((string) ($item['title'] ?? '')); ?>" placeholder="Title">
                            </div>
                            <?php if ($allowBody): ?>
                                <div class="admin-field">
                                    <label>Item Description</label>
                                    <input type="text" data-structured-body value="<?php echo admin_structured_h((string) ($item['body'] ?? '')); ?>" placeholder="Optional description">
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($allowUrl): ?>
                            <?php $urlValue = trim((string) ($item['url'] ?? '')); ?>
                            <?php $knownUrl = admin_structured_url_known($linkOptions, $urlValue); ?>
                            <div class="admin-grid cols-2">
                                <div class="admin-field">
                                    <label>Item Link</label>
                                    <select data-structured-url-choice>
                                        <option value="" <?php echo $urlValue === '' ? 'selected' : ''; ?>>No link</option>
                                        <?php admin_selection_render_option_groups($linkOptions, $knownUrl ? $urlValue : ''); ?>
                                        <option value="__custom__" <?php echo $urlValue !== '' && !$knownUrl ? 'selected' : ''; ?>>Custom URL</option>
                                    </select>
                                </div>
                                <div class="admin-field" data-structured-url-custom-wrap <?php echo $urlValue !== '' && !$knownUrl ? '' : 'hidden'; ?>>
                                    <label>Custom URL</label>
                                    <input type="text" data-structured-url-custom value="<?php echo admin_structured_h($urlValue); ?>" placeholder="/contact">
                                </div>
                            </div>
                        <?php endif; ?>
                        <?php admin_structured_render_row_controls(); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <template data-structured-template>
                <div class="admin-card admin-soft-card admin-structured-items__row" data-structured-row>
                    <div class="admin-grid cols-2">
                        <div class="admin-field">
                            <label>Item Title</label>
                            <input type="text" data-structured-title value="" placeholder="Title">
                        </div>
                        <?php if ($allowBody): ?>
                            <div class="admin-field">
                                <label>Item Description</label>
                                <input type="text" data-structured-body value="" placeholder="Optional description">
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($allowUrl): ?>
                        <div class="admin-grid cols-2">
                            <div class="admin-field">
                                <label>Item Link</label>
                                <select data-structured-url-choice>
                                    <option value="" selected>No link</option>
                                    <?php admin_selection_render_option_groups($linkOptions, ''); ?>
                                    <option value="__custom__">Custom URL</option>
                                </select>
                            </div>
                            <div class="admin-field" data-structured-url-custom-wrap hidden>
                                <label>Custom URL</label>
                                <input type="text" data-structured-url-custom value="" placeholder="/contact">
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php admin_structured_render_row_controls(); ?>
                </div>
            </template>
            <div class="admin-structured-items__footer">
                <button type="button" class="admin-button-secondary" data-structured-add-row><?php echo admin_structured_h($addLabel); ?></button>
                <span class="admin-note">Rows are saved in the exact order shown.</span>
            </div>
            <?php if ($note !== ''): ?>
                <p class="admin-note"><?php echo admin_structured_h($note); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
