$(document).ready(function() {

    const menuId = menu_builder_data.current_menu_id;
    const csrfToken = menu_builder_data.csrf_token;
    const nestable = $('#nestable-menu');

    // =================================================================
    // CORE FUNCTIONS
    // =================================================================

    // Helper for showing alerts/toasts
    function showAlert(message, type = 'success') {
        // A simple alert, but you can integrate a nicer toast library
        alert(message);
    }

    // Main AJAX function
    async function performAction(action, data = {}) {
        try {
            const response = await fetch('/admin/menu_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: action,
                    csrf_token: csrfToken,
                    ...data
                })
            });
            const result = await response.json();
            if (result.status !== 'success') {
                showAlert(result.message, 'danger');
            }
            return result;
        } catch (error) {
            console.error('AJAX Error:', error);
            showAlert('A network or server error occurred.', 'danger');
            return { status: 'error' };
        }
    }

    // =================================================================
    // MENU INITIALIZATION AND BUILDING
    // =================================================================
    
    function buildMenuItemHTML(item) {
        // This function creates the HTML for each menu item
        const isMegaMenu = item.is_mega_menu ? 'checked' : '';
        const targetBlank = item.target_blank ? 'checked' : '';
        
        return `
        <li class="dd-item" data-id="${item.id}">
            <div class="dd-handle">
                <span class="item-title">${item.title}</span>
                <span class="text-muted small ms-2">(${item.type || 'custom'})</span>
                <div class="dd-actions">
                    <a class="edit-item" title="Edit Item"><i class="bi bi-pencil-square"></i></a>
                    <a class="remove-item text-danger" title="Remove Item"><i class="bi bi-trash"></i></a>
                </div>
            </div>
            <div class="item-details">
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Navigation Label</label>
                        <input type="text" class="form-control form-control-sm" name="title" value="${item.title}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">URL</label>
                        <input type="text" class="form-control form-control-sm" name="url" value="${item.url}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Icon Class (e.g., bi bi-house)</label>
                        <input type="text" class="form-control form-control-sm" name="icon_class" value="${item.icon_class || ''}">
                    </div>
                    <div class="col-md-6 mb-2">
                        <label class="form-label">Custom CSS Class</label>
                        <input type="text" class="form-control form-control-sm" name="css_class" value="${item.css_class || ''}">
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control form-control-sm" name="description" value="${item.description || ''}">
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="target_blank" value="1" ${targetBlank}>
                            <label class="form-check-label small">Open in new tab</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="is_mega_menu" value="1" ${isMegaMenu}>
                            <label class="form-check-label small">Enable as Mega Menu</label>
                        </div>
                    </div>
                    <button class="btn btn-sm btn-primary save-item-details">Save Changes</button>
                </div>
            </div>
        </li>`;
    }

    function buildMenu(items, parentId = 0) {
        let html = '<ol class="dd-list">';
        const children = items.filter(item => item.parent_id == parentId);
        
        for (const item of children) {
            html += buildMenuItemHTML(item);
            const nestedHtml = buildMenu(items, item.id);
            if (nestedHtml.includes('li')) {
                html = html.slice(0, -5); // Remove last </li>
                html += nestedHtml + '</li>';
            }
        }
        html += '</ol>';
        return html;
    }
    
    async function loadMenu() {
        if (menuId > 0) {
            nestable.html('<div class="dd-empty">Loading menu...</div>');
            const result = await performAction('get_menu_items', { menu_id: menuId });
            if (result.status === 'success' && result.items) {
                const menuHtml = buildMenu(result.items);
                nestable.html(menuHtml);
                nestable.nestable({ maxDepth: 5 }); // Initialize nestable library
            }
        }
    }
    
    // Initial load
    loadMenu();
    
    // =================================================================
    // EVENT HANDLERS
    // =================================================================

    // Add items from left panel
    $('#form-add-custom-link, #form-add-pages, #form-add-categories').on('submit', async function(e) {
        e.preventDefault();
        
        let items_to_add = [];
        if (this.id === 'form-add-custom-link') {
            items_to_add.push({
                type: 'custom',
                url: $(this).find('[name="url"]').val(),
                title: $(this).find('[name="label"]').val()
            });
        } else {
            $(this).find('input[type="checkbox"]:checked').each(function() {
                items_to_add.push({
                    id: $(this).val(),
                    type: $(this).data('type'),
                    title: $(this).data('title'),
                    slug: $(this).data('slug') // Assuming you add data-slug to your checkboxes
                });
            });
        }

        if (items_to_add.length === 0) return;
        
        const result = await performAction('add_items', { menu_id: menuId, items: items_to_add });
        if (result.status === 'success') {
            showAlert(result.message);
            loadMenu(); // Reload menu to show new items
            this.reset();
        }
    });

    // Save menu structure (on button click)
    $('#save-menu-structure').on('click', async function() {
        const structure = nestable.nestable('serialize');
        const result = await performAction('update_menu_structure', { menu_id: menuId, structure });
        if (result.status === 'success') {
            showAlert(result.message);
        }
    });

    // Toggle item details editor
    nestable.on('click', '.edit-item', function() {
        $(this).closest('.dd-item').find('.item-details').first().slideToggle();
    });

    // Remove item from menu
    nestable.on('click', '.remove-item', async function() {
        if (!confirm('Are you sure you want to remove this item and all its children?')) return;
        
        const itemId = $(this).closest('.dd-item').data('id');
        const result = await performAction('delete_item', { id: itemId });
        if (result.status === 'success') {
            showAlert(result.message);
            // Remove from view without full reload
            $(this).closest('.dd-item').remove();
        }
    });

    // Save changes for a single item
    nestable.on('click', '.save-item-details', async function() {
        const detailsContainer = $(this).closest('.item-details');
        const itemId = $(this).closest('.dd-item').data('id');
        
        let details = { id: itemId };
        detailsContainer.find('input, select, textarea').each(function() {
            const name = $(this).attr('name');
            if ($(this).is(':checkbox')) {
                details[name] = $(this).is(':checked');
            } else {
                details[name] = $(this).val();
            }
        });
        
        const result = await performAction('update_item_details', { details });
        if (result.status === 'success') {
            showAlert(result.message);
            // Update the title in the handle
            detailsContainer.closest('.dd-item').find('.item-title').first().text(details.title);
            detailsContainer.slideUp();
        }
    });
    
    // Create new menu
    $('#create-new-menu-link').on('click', async function(e) {
        e.preventDefault();
        const name = prompt("Enter a name for the new menu:");
        if (name && name.trim() !== '') {
            const result = await performAction('create_menu', { name: name.trim() });
            if (result.status === 'success') {
                showAlert(result.message);
                window.location.href = `/admin/menus.php?menu_id=${result.new_id}`;
            }
        }
    });

    // Delete entire menu
    $('#delete-menu-btn').on('click', async function() {
        if (!confirm('Are you sure you want to permanently delete this entire menu? This cannot be undone.')) return;

        const result = await performAction('delete_menu', { menu_id: menuId });
        if (result.status === 'success') {
            showAlert(result.message);
            window.location.href = '/admin/menus.php';
        }
    });
    
    // Save menu locations
    $('#menu-locations-form').on('submit', async function(e) {
        e.preventDefault();
        let locations = {};
        $(this).find('select').each(function() {
            locations[$(this).attr('name')] = $(this).val();
        });
        
        const result = await performAction('save_locations', { locations });
        if (result.status === 'success') {
            showAlert(result.message);
        }
    });

});