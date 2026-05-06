<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((string) ($pageTitle ?? 'Admin'), ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <?php
    $currentAdminPath = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/admin'), PHP_URL_PATH) ?: '/admin';
    $isActiveAdminLink = static function (string $path) use ($currentAdminPath): bool {
        if ($currentAdminPath === $path) {
            return true;
        }

        if ($path !== '/admin' && str_starts_with($currentAdminPath, $path . '/')) {
            return true;
        }

        return false;
    };
    $adminNavLinkClass = static function (string $path) use ($isActiveAdminLink): string {
        $classes = ['admin-nav__link'];
        if ($isActiveAdminLink($path)) {
            $classes[] = 'is-active';
        }

        return implode(' ', $classes);
    };
    ?>
    <div class="admin-shell">
        <aside class="admin-sidebar" id="admin-sidebar">
            <div class="admin-brand">
                <div class="admin-brand__mark">LR</div>
                <div>
                    <div class="admin-brand__eyebrow"><?php echo htmlspecialchars((string) settings('store_name', 'Lily and Rose'), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="admin-brand__page"><?php echo htmlspecialchars((string) ($pageTitle ?? 'Admin'), ENT_QUOTES, 'UTF-8'); ?></div>
                </div>
            </div>

            <?php if (!empty($showLogoutLink)): ?>
                <button type="button" class="admin-mobile-nav-close" data-admin-nav-close aria-label="Close admin menu">Close</button>
                <p class="admin-sidebar__label">Catalog</p>
                <nav class="admin-nav">
                    <a href="/admin" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin'), ENT_QUOTES, 'UTF-8'); ?>"><span>Dashboard</span><span class="admin-nav__badge">01</span></a>
                    <a href="/admin/categories" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/categories'), ENT_QUOTES, 'UTF-8'); ?>"><span>Categories</span><span class="admin-nav__badge">02</span></a>
                    <a href="/admin/occasions" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/occasions'), ENT_QUOTES, 'UTF-8'); ?>"><span>Occasions</span><span class="admin-nav__badge">03</span></a>
                    <a href="/admin/addons" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/addons'), ENT_QUOTES, 'UTF-8'); ?>"><span>Add-Ons</span><span class="admin-nav__badge">04</span></a>
                    <a href="/admin/promo-codes" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/promo-codes'), ENT_QUOTES, 'UTF-8'); ?>"><span>Promo Codes</span><span class="admin-nav__badge">05</span></a>
                    <a href="/admin/products" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/products'), ENT_QUOTES, 'UTF-8'); ?>"><span>Products</span><span class="admin-nav__badge">06</span></a>
                </nav>

                <p class="admin-sidebar__label">Orders</p>
                <nav class="admin-nav">
                    <a href="/admin/orders" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/orders'), ENT_QUOTES, 'UTF-8'); ?>"><span>Orders</span><span class="admin-nav__badge">07</span></a>
                    <a href="/admin/customers" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/customers'), ENT_QUOTES, 'UTF-8'); ?>"><span>Customers</span><span class="admin-nav__badge">08</span></a>
                    <a href="/admin/delivery-zones" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/delivery-zones'), ENT_QUOTES, 'UTF-8'); ?>"><span>Delivery Zones</span><span class="admin-nav__badge">09</span></a>
                </nav>

                <p class="admin-sidebar__label">Website</p>
                <nav class="admin-nav">
                    <a href="/admin/site-settings" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/site-settings'), ENT_QUOTES, 'UTF-8'); ?>"><span>Site Settings</span><span class="admin-nav__badge">10</span></a>
                    <a href="/admin/theme" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/theme'), ENT_QUOTES, 'UTF-8'); ?>"><span>Theme</span><span class="admin-nav__badge">11</span></a>
                    <a href="/admin/homepage" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/homepage'), ENT_QUOTES, 'UTF-8'); ?>"><span>Homepage</span><span class="admin-nav__badge">12</span></a>
                    <a href="/admin/public-pages" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/public-pages'), ENT_QUOTES, 'UTF-8'); ?>"><span>Public Pages</span><span class="admin-nav__badge">13</span></a>
                    <a href="/admin/navigation" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/navigation'), ENT_QUOTES, 'UTF-8'); ?>"><span>Navigation</span><span class="admin-nav__badge">14</span></a>
                    <a href="/admin/banners" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/banners'), ENT_QUOTES, 'UTF-8'); ?>"><span>Banners</span><span class="admin-nav__badge">15</span></a>
                    <a href="/admin/footer-seo" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/footer-seo'), ENT_QUOTES, 'UTF-8'); ?>"><span>Footer</span><span class="admin-nav__badge">16</span></a>
                    <a href="/admin/media" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/media'), ENT_QUOTES, 'UTF-8'); ?>"><span>Media</span><span class="admin-nav__badge">17</span></a>
                    <a href="/admin/email-settings" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/email-settings'), ENT_QUOTES, 'UTF-8'); ?>"><span>Email Settings</span><span class="admin-nav__badge">18</span></a>
                    <a href="/admin/email-campaigns" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/email-campaigns'), ENT_QUOTES, 'UTF-8'); ?>"><span>Email Campaigns</span><span class="admin-nav__badge">19</span></a>
                </nav>

                <p class="admin-sidebar__label">Operations</p>
                <nav class="admin-nav">
                    <a href="/admin/settings" class="<?php echo htmlspecialchars($adminNavLinkClass('/admin/settings'), ENT_QUOTES, 'UTF-8'); ?>"><span>Runtime Settings</span><span class="admin-nav__badge">20</span></a>
                </nav>

                <p class="admin-sidebar__label">Session</p>
                <nav class="admin-nav">
                    <form method="post" action="/admin/logout" style="margin:0;">
                        <?php echo csrf_field(); ?>
                        <button type="submit" class="admin-nav__button"><span>Logout</span><span class="admin-nav__badge">X</span></button>
                    </form>
                </nav>
            <?php else: ?>
                <p class="admin-sidebar__label">Access</p>
                <p style="margin:0;color:rgba(246,239,233,0.76);line-height:1.7;">Sign in to access the active florist admin runtime.</p>
            <?php endif; ?>
        </aside>

        <main class="admin-main">
            <div class="admin-topbar">
                <div class="admin-topbar__card">
                    <?php if (!empty($showLogoutLink)): ?>
                        <button type="button" class="admin-mobile-nav-toggle" data-admin-nav-toggle aria-controls="admin-sidebar" aria-expanded="false">Menu</button>
                    <?php endif; ?>
                    <p class="admin-kicker">Admin Area</p>
                    <h1 class="admin-title"><?php echo htmlspecialchars((string) ($pageTitle ?? 'Admin'), ENT_QUOTES, 'UTF-8'); ?></h1>
                    <?php if (!empty($adminEmail)): ?>
                        <p class="admin-subtitle">Signed in as <?php echo htmlspecialchars((string) $adminEmail, ENT_QUOTES, 'UTF-8'); ?></p>
                    <?php endif; ?>
                </div>
                <?php if (!empty($showLogoutLink)): ?>
                    <a href="/admin/site-settings" class="admin-button-secondary">Website Settings</a>
                <?php endif; ?>
            </div>

            <?php echo $content ?? ''; ?>
        </main>
    </div>
    <script>
        (function () {
            var navToggle = document.querySelector('[data-admin-nav-toggle]');
            var navClose = document.querySelector('[data-admin-nav-close]');
            var sidebar = document.getElementById('admin-sidebar');

            function setAdminNavOpen(isOpen) {
                if (!sidebar) {
                    return;
                }

                if (isOpen) {
                    sidebar.classList.add('is-open');
                    document.body.classList.add('admin-nav-open');
                } else {
                    sidebar.classList.remove('is-open');
                    document.body.classList.remove('admin-nav-open');
                }

                if (navToggle) {
                    navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
                }
            }

            if (navToggle) {
                navToggle.addEventListener('click', function () {
                    setAdminNavOpen(!sidebar.classList.contains('is-open'));
                });
            }

            if (navClose) {
                navClose.addEventListener('click', function () {
                    setAdminNavOpen(false);
                });
            }

            document.querySelectorAll('.admin-sidebar .admin-nav__link').forEach(function (link) {
                link.addEventListener('click', function () {
                    setAdminNavOpen(false);
                });
            });

            window.addEventListener('resize', function () {
                if (window.innerWidth > 900) {
                    setAdminNavOpen(false);
                }
            });

            document.querySelectorAll('.admin-table').forEach(function (table) {
                var headers = Array.prototype.slice.call(table.querySelectorAll('thead th')).map(function (th) {
                    return (th.textContent || '').trim();
                });

                if (headers.length === 0) {
                    return;
                }

                table.querySelectorAll('tbody tr').forEach(function (row) {
                    Array.prototype.slice.call(row.children).forEach(function (cell, index) {
                        if (cell && !cell.getAttribute('data-label') && headers[index]) {
                            cell.setAttribute('data-label', headers[index]);
                        }
                    });
                });
            });

            function findHybridWrap(targetId) {
                var wraps = document.querySelectorAll('[data-admin-hybrid-custom-wrap]');
                for (var i = 0; i < wraps.length; i += 1) {
                    if (wraps[i].getAttribute('data-admin-hybrid-custom-wrap') === targetId) {
                        return wraps[i];
                    }
                }
                return null;
            }

            function syncHybridSelect(select, focusCustom) {
                var targetId = select.getAttribute('data-admin-hybrid-target') || '';
                var target = document.getElementById(targetId);
                var wrap = findHybridWrap(targetId);

                if (!target || !wrap) {
                    return;
                }

                if (select.value === '__custom__') {
                    wrap.hidden = false;
                    if (focusCustom) {
                        target.focus();
                    }
                    return;
                }

                target.value = select.value;
                wrap.hidden = true;
            }

            function setHybridValue(targetId, value) {
                var target = document.getElementById(targetId);
                var selects = document.querySelectorAll('[data-admin-hybrid-select]');
                var select = null;

                for (var i = 0; i < selects.length; i += 1) {
                    if (selects[i].getAttribute('data-admin-hybrid-target') === targetId) {
                        select = selects[i];
                        break;
                    }
                }

                if (target) {
                    target.value = value;
                }

                if (!select) {
                    return;
                }

                var hasKnownOption = false;
                for (var optionIndex = 0; optionIndex < select.options.length; optionIndex += 1) {
                    if (select.options[optionIndex].value === value) {
                        hasKnownOption = true;
                        break;
                    }
                }

                select.value = hasKnownOption ? value : (value === '' ? '' : '__custom__');
                syncHybridSelect(select, false);
            }

            document.querySelectorAll('[data-admin-hybrid-select]').forEach(function (select) {
                syncHybridSelect(select, false);
                select.addEventListener('change', function () {
                    syncHybridSelect(select, true);
                });
            });

            document.querySelectorAll('[data-admin-filter-input]').forEach(function (input) {
                var list = document.getElementById(input.getAttribute('data-admin-filter-target') || '');
                if (!list) {
                    return;
                }

                input.addEventListener('input', function () {
                    var query = input.value.trim().toLowerCase();
                    list.querySelectorAll('[data-admin-filter-item]').forEach(function (item) {
                        var text = (item.getAttribute('data-admin-filter-text') || item.textContent || '').toLowerCase();
                        item.hidden = query !== '' && text.indexOf(query) === -1;
                    });
                });
            });

            function updateMultiCount(wrap, list) {
                var countNode = wrap ? wrap.querySelector('[data-admin-multi-count]') : null;
                if (!countNode || !list) {
                    return;
                }

                var selectedCount = list.querySelectorAll('input[type="checkbox"]:checked').length;
                countNode.textContent = selectedCount + ' selected';
            }

            document.querySelectorAll('[data-admin-multi-controls]').forEach(function (wrap) {
                var targetId = wrap.getAttribute('data-admin-filter-target') || '';
                var list = document.getElementById(targetId);
                if (!list) {
                    return;
                }

                function applyAction(action) {
                    var checkboxes = list.querySelectorAll('input[type="checkbox"]');
                    if (action === 'all') {
                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = true;
                        });
                    } else if (action === 'visible') {
                        list.querySelectorAll('[data-admin-filter-item]').forEach(function (item) {
                            if (item.hidden) {
                                return;
                            }
                            var checkbox = item.querySelector('input[type="checkbox"]');
                            if (checkbox) {
                                checkbox.checked = true;
                            }
                        });
                    } else if (action === 'clear') {
                        checkboxes.forEach(function (checkbox) {
                            checkbox.checked = false;
                        });
                    }
                    updateMultiCount(wrap, list);
                }

                wrap.querySelectorAll('[data-admin-multi-action]').forEach(function (button) {
                    button.addEventListener('click', function () {
                        applyAction(button.getAttribute('data-admin-multi-action') || '');
                    });
                });

                list.querySelectorAll('input[type="checkbox"]').forEach(function (checkbox) {
                    checkbox.addEventListener('change', function () {
                        updateMultiCount(wrap, list);
                    });
                });

                updateMultiCount(wrap, list);
            });

            function syncStructuredUrlRow(row) {
                var choice = row.querySelector('[data-structured-url-choice]');
                var customWrap = row.querySelector('[data-structured-url-custom-wrap]');

                if (!choice || !customWrap) {
                    return;
                }

                customWrap.hidden = choice.value !== '__custom__';
            }

            function buildStructuredLine(row, allowBody, allowUrl) {
                var titleInput = row.querySelector('[data-structured-title]');
                var bodyInput = row.querySelector('[data-structured-body]');
                var urlChoice = row.querySelector('[data-structured-url-choice]');
                var customUrlInput = row.querySelector('[data-structured-url-custom]');
                var title = titleInput ? titleInput.value.trim() : '';
                var body = allowBody && bodyInput ? bodyInput.value.trim() : '';
                var url = '';

                if (allowUrl && urlChoice) {
                    if (urlChoice.value === '__custom__') {
                        url = customUrlInput ? customUrlInput.value.trim() : '';
                    } else {
                        url = urlChoice.value.trim();
                    }
                }

                if (title === '') {
                    return '';
                }

                if (allowBody && allowUrl) {
                    if (body !== '' && url !== '') {
                        return title + '|' + body + '|' + url;
                    }

                    if (body !== '') {
                        return title + '|' + body;
                    }

                    if (url !== '') {
                        return title + '|' + url;
                    }

                    return title;
                }

                if (allowUrl) {
                    return url !== '' ? title + '|' + url : title;
                }

                if (allowBody) {
                    return body !== '' ? title + '|' + body : title;
                }

                return title;
            }

            function serializeStructuredEditor(editor) {
                var allowBody = editor.getAttribute('data-allow-body') === '1';
                var allowUrl = editor.getAttribute('data-allow-url') === '1';
                var output = editor.querySelector('[data-structured-output]');
                var summary = editor.querySelector('[data-structured-summary]');
                var rows = Array.prototype.slice.call(editor.querySelectorAll('[data-structured-row]'));
                var lines = [];

                rows.forEach(function (row) {
                    var line = buildStructuredLine(row, allowBody, allowUrl);

                    if (line !== '') {
                        lines.push(line);
                    }
                });

                if (output) {
                    output.value = lines.join('\n');
                }

                if (summary) {
                    summary.textContent = lines.length > 0
                        ? String(lines.length) + ' saved item(s) ready to save.'
                        : 'No saved items yet.';
                }
            }

            function bindStructuredRow(editor, row) {
                row.querySelectorAll('input, textarea, select').forEach(function (field) {
                    field.addEventListener('input', function () {
                        serializeStructuredEditor(editor);
                    });
                    field.addEventListener('change', function () {
                        syncStructuredUrlRow(row);
                        serializeStructuredEditor(editor);
                    });
                });

                var removeButton = row.querySelector('[data-structured-remove-row]');
                if (removeButton) {
                    removeButton.addEventListener('click', function () {
                        row.remove();
                        serializeStructuredEditor(editor);
                    });
                }

                var upButton = row.querySelector('[data-structured-move-up]');
                if (upButton) {
                    upButton.addEventListener('click', function () {
                        var previous = row.previousElementSibling;
                        if (previous) {
                            row.parentNode.insertBefore(row, previous);
                            serializeStructuredEditor(editor);
                        }
                    });
                }

                var downButton = row.querySelector('[data-structured-move-down]');
                if (downButton) {
                    downButton.addEventListener('click', function () {
                        var next = row.nextElementSibling;
                        if (next) {
                            row.parentNode.insertBefore(next, row);
                            serializeStructuredEditor(editor);
                        }
                    });
                }

                syncStructuredUrlRow(row);
            }

            document.querySelectorAll('[data-structured-editor]').forEach(function (editor) {
                var rowsWrap = editor.querySelector('[data-structured-rows]');
                var template = editor.querySelector('template[data-structured-template]');
                var addButton = editor.querySelector('[data-structured-add-row]');

                if (!rowsWrap || !template || !addButton) {
                    return;
                }

                rowsWrap.querySelectorAll('[data-structured-row]').forEach(function (row) {
                    bindStructuredRow(editor, row);
                });

                addButton.addEventListener('click', function () {
                    var fragment = template.content.cloneNode(true);
                    var newRow = fragment.querySelector('[data-structured-row]');
                    rowsWrap.appendChild(fragment);

                    if (newRow) {
                        bindStructuredRow(editor, newRow);
                        var title = newRow.querySelector('[data-structured-title]');
                        if (title) {
                            title.focus();
                        }
                    }

                    serializeStructuredEditor(editor);
                });

                var form = editor.closest('form');
                if (form) {
                    form.addEventListener('submit', function () {
                        serializeStructuredEditor(editor);
                    });
                }

                serializeStructuredEditor(editor);
            });

            function syncHomepageProductList(editor) {
                var list = editor.querySelector('[data-homepage-selected-products]');
                var emptyState = list ? list.querySelector('[data-homepage-empty-products]') : null;
                var rows = list ? Array.prototype.slice.call(list.querySelectorAll('[data-homepage-selected-item]')) : [];

                rows.forEach(function (row, index) {
                    var productId = row.getAttribute('data-product-id') || '';
                    var idInput = row.querySelector('[data-homepage-product-id-input]');
                    var orderInput = row.querySelector('[data-homepage-product-order-input]');
                    var position = row.querySelector('[data-homepage-product-position]');

                    if (idInput) {
                        idInput.name = (editor.getAttribute('data-field-prefix') || 'sections[0]') + '[product_ids][]';
                        idInput.value = productId;
                    }

                    if (orderInput) {
                        orderInput.name = (editor.getAttribute('data-field-prefix') || 'sections[0]') + '[product_sort_orders][' + productId + ']';
                        orderInput.value = String((index + 1) * 10);
                    }

                    if (position) {
                        position.textContent = String(index + 1);
                    }
                });

                if (emptyState) {
                    emptyState.hidden = rows.length > 0;
                }
            }

            function bindHomepageProductRow(editor, row) {
                var upButton = row.querySelector('[data-homepage-product-move-up]');
                var downButton = row.querySelector('[data-homepage-product-move-down]');
                var removeButton = row.querySelector('[data-homepage-product-remove]');
                var parentForm = editor.closest('form');

                if (upButton) {
                    upButton.addEventListener('click', function () {
                        var previous = row.previousElementSibling;
                        if (previous && previous.hasAttribute('data-homepage-selected-item')) {
                            row.parentNode.insertBefore(row, previous);
                            syncHomepageProductList(editor);
                            if (parentForm) {
                                parentForm.setAttribute('data-homepage-slider-dirty', '1');
                            }
                        }
                    });
                }

                if (downButton) {
                    downButton.addEventListener('click', function () {
                        var next = row.nextElementSibling;
                        if (next && next.hasAttribute('data-homepage-selected-item')) {
                            row.parentNode.insertBefore(next, row);
                            syncHomepageProductList(editor);
                            if (parentForm) {
                                parentForm.setAttribute('data-homepage-slider-dirty', '1');
                            }
                        }
                    });
                }

                if (removeButton) {
                    removeButton.addEventListener('click', function () {
                        row.remove();
                        syncHomepageProductList(editor);
                        if (parentForm) {
                            parentForm.setAttribute('data-homepage-slider-dirty', '1');
                        }
                    });
                }
            }

            function addHomepageProduct(editor, productId, productName, productMeta) {
                var list = editor.querySelector('[data-homepage-selected-products]');
                var template = editor.querySelector('template[data-homepage-product-template]');

                if (!list || !template || !productId) {
                    return;
                }

                if (list.querySelector('[data-homepage-selected-item][data-product-id="' + productId + '"]')) {
                    return;
                }

                var fragment = template.content.cloneNode(true);
                var row = fragment.querySelector('[data-homepage-selected-item]');

                if (!row) {
                    return;
                }

                row.setAttribute('data-product-id', productId);
                var nameNode = row.querySelector('[data-homepage-product-name]');
                var metaNode = row.querySelector('[data-homepage-product-meta]');

                if (nameNode) {
                    nameNode.textContent = productName || 'Product';
                }

                if (metaNode) {
                    metaNode.textContent = productMeta || '';
                }

                list.appendChild(fragment);
                bindHomepageProductRow(editor, row);
                syncHomepageProductList(editor);
                var parentForm = editor.closest('form');
                if (parentForm) {
                    parentForm.setAttribute('data-homepage-slider-dirty', '1');
                }
            }

            document.querySelectorAll('[data-homepage-products-editor]').forEach(function (editor) {
                editor.querySelectorAll('[data-homepage-selected-item]').forEach(function (row) {
                    bindHomepageProductRow(editor, row);
                });

                var addSelect = editor.querySelector('[data-homepage-add-product]');
                var addButton = editor.querySelector('[data-homepage-add-product-button]');

                if (addSelect && addButton) {
                    addButton.addEventListener('click', function () {
                        var option = addSelect.selectedOptions && addSelect.selectedOptions[0] ? addSelect.selectedOptions[0] : null;
                        if (!option || option.value === '') {
                            return;
                        }

                        addHomepageProduct(
                            editor,
                            option.value,
                            option.getAttribute('data-product-name') || option.textContent || 'Product',
                            option.getAttribute('data-product-meta') || option.textContent || ''
                        );
                        addSelect.value = '';
                    });
                }

                syncHomepageProductList(editor);
            });

            function syncHomepageSectionOrder(form) {
                var index = 1;

                form.querySelectorAll('[data-homepage-section-card]').forEach(function (card) {
                    var orderInput = card.querySelector('[data-homepage-section-order-input]');
                    var position = card.querySelector('[data-homepage-section-position]');

                    if (!orderInput) {
                        return;
                    }

                    orderInput.value = String(index * 10);

                    if (position) {
                        position.textContent = String(index);
                    }

                    index += 1;
                });
            }

            document.querySelectorAll('[data-homepage-sections-form]').forEach(function (form) {
                form.setAttribute('data-homepage-slider-dirty', '0');
                form.querySelectorAll('[data-homepage-section-card]').forEach(function (card) {
                    var upButton = card.querySelector('[data-homepage-section-move-up]');
                    var downButton = card.querySelector('[data-homepage-section-move-down]');

                    if (upButton) {
                        upButton.addEventListener('click', function () {
                            var previous = card.previousElementSibling;
                            while (previous && !previous.hasAttribute('data-homepage-section-card')) {
                                previous = previous.previousElementSibling;
                            }

                            if (previous) {
                                form.insertBefore(card, previous);
                                syncHomepageSectionOrder(form);
                                form.setAttribute('data-homepage-slider-dirty', '1');
                            }
                        });
                    }

                    if (downButton) {
                        downButton.addEventListener('click', function () {
                            var next = card.nextElementSibling;
                            while (next && !next.hasAttribute('data-homepage-section-card')) {
                                next = next.nextElementSibling;
                            }

                            if (next) {
                                form.insertBefore(next, card);
                                syncHomepageSectionOrder(form);
                                form.setAttribute('data-homepage-slider-dirty', '1');
                            }
                        });
                    }
                });

                form.querySelectorAll('input, select, textarea, button').forEach(function (field) {
                    field.addEventListener('change', function () {
                        form.setAttribute('data-homepage-slider-dirty', '1');
                    });
                });

                form.addEventListener('submit', function () {
                    syncHomepageSectionOrder(form);
                    form.querySelectorAll('[data-homepage-products-editor]').forEach(function (editor) {
                        syncHomepageProductList(editor);
                    });
                    form.setAttribute('data-homepage-slider-dirty', '0');
                });

                syncHomepageSectionOrder(form);

                var contentForm = document.getElementById('homepage-content-sections');
                if (contentForm) {
                    contentForm.addEventListener('submit', function (event) {
                        var dirty = false;
                        document.querySelectorAll('[data-homepage-sections-form]').forEach(function (sliderForm) {
                            if (sliderForm.getAttribute('data-homepage-slider-dirty') === '1') {
                                dirty = true;
                            }
                        });

                        if (!dirty) {
                            return;
                        }

                        event.preventDefault();
                        window.alert('You changed product sliders but have not saved them yet. Click \"Save Product Sliders\" first.');
                    });
                }
            });

            document.querySelectorAll('[data-homepage-section-preset]').forEach(function (select) {
                select.addEventListener('change', function () {
                    var option = select.selectedOptions && select.selectedOptions[0] ? select.selectedOptions[0] : null;
                    var card = select.closest('.homepage-section-card');
                    var editor = card ? card.querySelector('[data-homepage-products-editor]') : null;

                    if (!option || option.value === '' || !card) {
                        return;
                    }

                    var titleInput = card.querySelector('[name="new_section[title]"]');
                    var subheadingInput = card.querySelector('[name="new_section[subheading]"]');
                    var ctaLabelInput = card.querySelector('[name="new_section[cta_label]"]');

                    if (titleInput) {
                        titleInput.value = option.getAttribute('data-section-title') || titleInput.value;
                    }

                    if (subheadingInput) {
                        subheadingInput.value = option.getAttribute('data-section-subheading') || subheadingInput.value;
                    }

                    if (ctaLabelInput) {
                        ctaLabelInput.value = option.getAttribute('data-section-cta-label') || ctaLabelInput.value;
                    }

                    setHybridValue('new_homepage_section_cta_url', option.getAttribute('data-section-cta-url') || '');

                    if (editor) {
                        editor.querySelectorAll('[data-homepage-selected-item]').forEach(function (row) {
                            row.remove();
                        });

                        (option.getAttribute('data-product-ids') || '').split(',').filter(Boolean).forEach(function (productId) {
                            var addSelect = editor.querySelector('[data-homepage-add-product]');
                            var productOption = addSelect ? addSelect.querySelector('option[value="' + productId + '"]') : null;

                            if (productOption) {
                                addHomepageProduct(
                                    editor,
                                    productId,
                                    productOption.getAttribute('data-product-name') || productOption.textContent || 'Product',
                                    productOption.getAttribute('data-product-meta') || productOption.textContent || ''
                                );
                            }
                        });

                        syncHomepageProductList(editor);
                    }
                });
            });
        }());
    </script>
</body>
</html>
