<?php
/**
 * Advanced Tags & Categories API
 * Manage tag groups, tags, and taggable items
 */

require_once '../config.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    
    if (!$action) {
        throw new Exception('Missing action parameter');
    }

    // Get all tag groups
    if ($action === 'get_tag_groups') {
        $stmt = $db->prepare("
            SELECT id, name_ar, name_en, color_code, display_order 
            FROM tag_groups 
            WHERE is_active = TRUE
            ORDER BY display_order ASC
        ");
        $stmt->execute();
        
        return jsonResponse($stmt->fetchAll());
    }

    // Get tags by group
    if ($action === 'get_tags') {
        $group_id = $_GET['group_id'] ?? null;
        $featured_only = $_GET['featured_only'] ?? false;

        $sql = "SELECT id, name_ar, name_en, slug, color_code, usage_count FROM tags WHERE 1=1";
        if ($group_id) $sql .= " AND tag_group_id = " . intval($group_id);
        if ($featured_only) $sql .= " AND is_featured = TRUE";
        $sql .= " ORDER BY usage_count DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        return jsonResponse($stmt->fetchAll());
    }

    // Get all tags
    if ($action === 'all_tags') {
        $stmt = $db->prepare("
            SELECT id, name_ar, slug, tag_group_id, color_code, usage_count
            FROM tags
            ORDER BY usage_count DESC
        ");
        $stmt->execute();
        
        return jsonResponse($stmt->fetchAll());
    }

    // Get category hierarchy
    if ($action === 'get_category_tree') {
        $sql = "
            SELECT 
                c.id, c.name_ar, c.name_en, c.slug, c.description_ar,
                COUNT(ch.child_id) as children_count,
                GROUP_CONCAT(ch.child_id) as child_ids
            FROM categories c
            LEFT JOIN category_hierarchy ch ON c.id = ch.parent_id
            WHERE c.status = 'active'
            GROUP BY c.id
            ORDER BY c.display_order ASC
        ";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        $categories = $stmt->fetchAll();

        // Build tree structure
        $categoryTree = buildCategoryTree($categories);
        
        return jsonResponse($categoryTree);
    }

    // Get items by tag
    if ($action === 'get_by_tag') {
        $tag_slug = $_GET['tag_slug'] ?? null;
        if (!$tag_slug) throw new Exception('Missing tag_slug');

        $stmt = $db->prepare("
            SELECT t.id FROM tags t WHERE t.slug = ?
        ");
        $stmt->execute([$tag_slug]);
        $tag = $stmt->fetch();

        if (!$tag) throw new Exception('Tag not found', 404);

        $itemsStmt = $db->prepare("
            SELECT 
                ti.taggable_type, ti.taggable_id,
                CASE 
                    WHEN ti.taggable_type = 'article' THEN (SELECT title FROM articles WHERE id = ti.taggable_id)
                    WHEN ti.taggable_type = 'product' THEN (SELECT name_ar FROM products WHERE id = ti.taggable_id)
                END as title
            FROM taggable_items ti
            WHERE ti.tag_id = ?
            LIMIT 50
        ");
        $itemsStmt->execute([$tag['id']]);

        return jsonResponse($itemsStmt->fetchAll());
    }

    // Create tag (admin only)
    if ($action === 'create_tag') {
        if (!isAdmin()) return unauthorized();

        $name_ar = $_POST['name_ar'] ?? null;
        $group_id = $_POST['group_id'] ?? null;
        
        if (!$name_ar) throw new Exception('Missing name_ar');

        $slug = sanitizeSlug($name_ar);
        $stmt = $db->prepare("
            INSERT INTO tags (tag_group_id, name_ar, name_en, slug, color_code)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $group_id, $name_ar, $_POST['name_en'] ?? null, $slug,
            $_POST['color_code'] ?? '#3498db'
        ]);

        return jsonResponse(['success' => true, 'tag_id' => $db->lastInsertId()]);
    }

    // Add tag to item
    if ($action === 'tag_item') {
        if (!isAdmin()) return unauthorized();

        $tag_id = $_POST['tag_id'] ?? null;
        $taggable_type = $_POST['taggable_type'] ?? null;
        $taggable_id = $_POST['taggable_id'] ?? null;

        if (!$tag_id || !$taggable_type || !$taggable_id) {
            throw new Exception('Missing required fields');
        }

        $stmt = $db->prepare("
            INSERT IGNORE INTO taggable_items (tag_id, taggable_type, taggable_id)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$tag_id, $taggable_type, $taggable_id]);

        // Update usage count
        $updateStmt = $db->prepare("
            UPDATE tags SET usage_count = (
                SELECT COUNT(*) FROM taggable_items WHERE tag_id = ?
            ) WHERE id = ?
        ");
        $updateStmt->execute([$tag_id, $tag_id]);

        return jsonResponse(['success' => true]);
    }

    // Create tag group (admin only)
    if ($action === 'create_tag_group') {
        if (!isAdmin()) return unauthorized();

        $name_ar = $_POST['name_ar'] ?? null;
        if (!$name_ar) throw new Exception('Missing name_ar');

        $stmt = $db->prepare("
            INSERT INTO tag_groups (name_ar, name_en, color_code)
            VALUES (?, ?, ?)
        ");
        $stmt->execute([
            $name_ar, $_POST['name_en'] ?? null,
            $_POST['color_code'] ?? '#3498db'
        ]);

        return jsonResponse(['success' => true, 'group_id' => $db->lastInsertId()]);
    }

    // Add category child
    if ($action === 'add_category_child') {
        if (!isAdmin()) return unauthorized();

        $parent_id = $_POST['parent_id'] ?? null;
        $child_id = $_POST['child_id'] ?? null;

        if (!$parent_id || !$child_id) throw new Exception('Missing parent_id or child_id');

        $stmt = $db->prepare("
            INSERT IGNORE INTO category_hierarchy (parent_id, child_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$parent_id, $child_id]);

        return jsonResponse(['success' => true]);
    }

    throw new Exception('Invalid action');

} catch (Exception $e) {
    return errorResponse($e->getMessage(), 400);
}

function buildCategoryTree($categories, $parent_id = null) {
    $tree = [];
    
    foreach ($categories as $cat) {
        if ($parent_id === null && $cat['children_count'] == 0) {
            $tree[] = $cat;
        }
    }
    
    return $tree;
}

function jsonResponse($data) {
    echo json_encode(['success' => true, 'data' => $data]);
    exit;
}

function errorResponse($message, $code = 400) {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function unauthorized() {
    return errorResponse('Unauthorized', 403);
}

function sanitizeSlug($text) {
    return strtolower(trim(preg_replace('/[^a-zA-Z0-9\u0600-\u06FF-]/', '-', $text), '-'));
}
