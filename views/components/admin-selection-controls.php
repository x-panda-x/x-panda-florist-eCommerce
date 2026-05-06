<?php

declare(strict_types=1);

if (!function_exists('admin_selection_h')) {
    function admin_selection_h(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('admin_selection_find_value')) {
    /**
     * @param array<int, array<string, mixed>> $groups
     */
    function admin_selection_find_value(array $groups, string $value): ?string
    {
        foreach ($groups as $group) {
            foreach (($group['options'] ?? []) as $option) {
                if ((string) ($option['value'] ?? '') === $value) {
                    return $value;
                }
            }
        }

        return null;
    }
}

if (!function_exists('admin_selection_render_option_groups')) {
    /**
     * @param array<int, array<string, mixed>> $groups
     */
    function admin_selection_render_option_groups(array $groups, string $selectedValue): void
    {
        foreach ($groups as $group) {
            $label = (string) ($group['label'] ?? '');
            $options = is_array($group['options'] ?? null) ? $group['options'] : [];

            if ($options === []) {
                continue;
            }

            echo '<optgroup label="' . admin_selection_h($label) . '">';

            foreach ($options as $option) {
                $value = (string) ($option['value'] ?? '');
                $optionLabel = (string) ($option['label'] ?? $value);
                $attrs = [
                    'value="' . admin_selection_h($value) . '"',
                    $value === $selectedValue ? 'selected' : '',
                ];
                $data = is_array($option['data'] ?? null) ? $option['data'] : [];

                foreach ($data as $dataKey => $dataValue) {
                    if (!is_scalar($dataValue)) {
                        continue;
                    }

                    $normalizedKey = strtolower(preg_replace('/[^a-zA-Z0-9_-]+/', '-', (string) $dataKey) ?? '');

                    if ($normalizedKey === '') {
                        continue;
                    }

                    $attrs[] = 'data-' . admin_selection_h($normalizedKey) . '="' . admin_selection_h((string) $dataValue) . '"';
                }

                echo '<option ' . implode(' ', array_filter($attrs)) . '>' . admin_selection_h($optionLabel) . '</option>';
            }

            echo '</optgroup>';
        }
    }
}

if (!function_exists('admin_selection_render_hybrid_field')) {
    /**
     * @param array{
     *   id: string,
     *   name: string,
     *   label: string,
     *   value?: string,
     *   options: array<int, array<string, mixed>>,
     *   empty_label?: string,
     *   custom_label?: string,
     *   custom_input_label?: string,
     *   placeholder?: string,
     *   note?: string,
     *   required?: bool
     * } $args
     */
    function admin_selection_render_hybrid_field(array $args): void
    {
        $id = (string) $args['id'];
        $name = (string) $args['name'];
        $label = (string) $args['label'];
        $value = (string) ($args['value'] ?? '');
        $groups = is_array($args['options'] ?? null) ? $args['options'] : [];
        $emptyLabel = (string) ($args['empty_label'] ?? 'Choose an existing option');
        $customLabel = (string) ($args['custom_label'] ?? 'Custom / external');
        $customInputLabel = (string) ($args['custom_input_label'] ?? 'Custom value');
        $placeholder = (string) ($args['placeholder'] ?? '');
        $note = (string) ($args['note'] ?? '');
        $required = !empty($args['required']);
        $knownValue = admin_selection_find_value($groups, $value);
        $isCustom = $value !== '' && $knownValue === null;
        $selectedValue = $isCustom ? '__custom__' : ($knownValue ?? '');
        $selectId = $id . '_choice';

        echo '<div class="admin-field admin-hybrid-field">';
        echo '<label for="' . admin_selection_h($selectId) . '">' . admin_selection_h($label) . '</label>';
        echo '<select id="' . admin_selection_h($selectId) . '" data-admin-hybrid-select data-admin-hybrid-target="' . admin_selection_h($id) . '"' . ($required ? ' required' : '') . '>';
        echo '<option value=""' . ($selectedValue === '' ? ' selected' : '') . '>' . admin_selection_h($emptyLabel) . '</option>';
        admin_selection_render_option_groups($groups, $selectedValue);
        echo '<option value="__custom__"' . ($selectedValue === '__custom__' ? ' selected' : '') . '>' . admin_selection_h($customLabel) . '</option>';
        echo '</select>';

        echo '<div class="admin-hybrid-field__custom" data-admin-hybrid-custom-wrap="' . admin_selection_h($id) . '"' . (!$isCustom ? ' hidden' : '') . '>';
        echo '<label for="' . admin_selection_h($id) . '">' . admin_selection_h($customInputLabel) . '</label>';
        echo '<input id="' . admin_selection_h($id) . '" name="' . admin_selection_h($name) . '" type="text" value="' . admin_selection_h($value) . '" placeholder="' . admin_selection_h($placeholder) . '" data-admin-hybrid-custom>';
        echo '</div>';

        if ($note !== '') {
            echo '<small class="admin-note">' . admin_selection_h($note) . '</small>';
        }

        echo '</div>';
    }
}

if (!function_exists('admin_selection_render_filter_input')) {
    function admin_selection_render_filter_input(string $targetId, string $placeholder = 'Search choices'): void
    {
        echo '<div class="admin-field admin-filter-field">';
        echo '<label for="' . admin_selection_h($targetId) . '_filter">Search Choices</label>';
        echo '<input id="' . admin_selection_h($targetId) . '_filter" type="search" placeholder="' . admin_selection_h($placeholder) . '" data-admin-filter-input data-admin-filter-target="' . admin_selection_h($targetId) . '">';
        echo '</div>';
    }
}

if (!function_exists('admin_selection_render_bulk_controls')) {
    function admin_selection_render_bulk_controls(string $targetId): void
    {
        echo '<div class="admin-multi-controls" data-admin-multi-controls data-admin-filter-target="' . admin_selection_h($targetId) . '">';
        echo '<div class="admin-multi-controls__actions">';
        echo '<button type="button" class="admin-button-secondary" data-admin-multi-action="all">Select All</button>';
        echo '<button type="button" class="admin-button-secondary" data-admin-multi-action="visible">Select Visible</button>';
        echo '<button type="button" class="admin-button-secondary" data-admin-multi-action="clear">Clear All</button>';
        echo '</div>';
        echo '<p class="admin-note admin-multi-controls__count" data-admin-multi-count>0 selected</p>';
        echo '</div>';
    }
}
