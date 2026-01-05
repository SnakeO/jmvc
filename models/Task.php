<?php
/**
 * Task Model
 *
 * Represents a task in the task management system.
 * Uses post meta for custom fields (works without ACF).
 */

declare(strict_types=1);

class Task extends JModelBase {

    public static ?string $post_type = 'task';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';

    /**
     * Meta field keys
     */
    private const META_STATUS = '_task_status';
    private const META_PRIORITY = '_task_priority';
    private const META_DUE_DATE = '_task_due_date';
    private const META_TASK_LIST_ID = '_task_list_id';
    private const META_COMPLETED_AT = '_task_completed_at';

    public static function getStatuses(): array {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed'
        ];
    }

    public static function getPriorities(): array {
        return [
            self::PRIORITY_LOW => ['label' => 'Low', 'color' => '#28a745'],
            self::PRIORITY_MEDIUM => ['label' => 'Medium', 'color' => '#ffc107'],
            self::PRIORITY_HIGH => ['label' => 'High', 'color' => '#dc3545']
        ];
    }

    /**
     * Add a new task
     */
    public function add(): int|false {
        $post_data = [
            'post_type'    => static::$post_type,
            'post_title'   => $this->data['post_title'] ?? 'Untitled Task',
            'post_content' => $this->data['post_content'] ?? '',
            'post_status'  => 'publish',
        ];

        $post_id = wp_insert_post($post_data, true);

        if (is_wp_error($post_id)) {
            return false;
        }

        $this->id = $post_id;
        $this->updateMeta();

        return $this->id;
    }

    /**
     * Update the task
     */
    public function update(): bool {
        if (!$this->id) {
            return false;
        }

        // Update post data if changed
        $post_update = [];
        if (isset($this->data['post_title'])) {
            $post_update['post_title'] = $this->data['post_title'];
        }
        if (isset($this->data['post_content'])) {
            $post_update['post_content'] = $this->data['post_content'];
        }

        if (!empty($post_update)) {
            $post_update['ID'] = $this->id;
            wp_update_post($post_update);
        }

        $this->updateMeta();
        return true;
    }

    /**
     * Update meta fields
     */
    private function updateMeta(): void {
        if (!$this->id) {
            return;
        }

        $meta_map = [
            'status' => self::META_STATUS,
            'priority' => self::META_PRIORITY,
            'due_date' => self::META_DUE_DATE,
            'task_list_id' => self::META_TASK_LIST_ID,
            'completed_at' => self::META_COMPLETED_AT,
        ];

        foreach ($meta_map as $field => $meta_key) {
            if (isset($this->data[$field])) {
                update_post_meta($this->id, $meta_key, $this->data[$field]);
            }
        }
    }

    /**
     * Delete the task
     */
    public function delete(): bool {
        if (!$this->id) {
            return false;
        }

        $result = wp_delete_post($this->id, true);
        return $result !== false && $result !== null;
    }

    /**
     * Get a meta field value
     */
    public function __get(string $key): mixed {
        // Check data array first
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }

        // Map custom fields to meta
        $meta_map = [
            'status' => self::META_STATUS,
            'priority' => self::META_PRIORITY,
            'due_date' => self::META_DUE_DATE,
            'task_list_id' => self::META_TASK_LIST_ID,
            'completed_at' => self::META_COMPLETED_AT,
        ];

        if (isset($meta_map[$key]) && $this->id) {
            return get_post_meta($this->id, $meta_map[$key], true) ?: null;
        }

        // Fall back to post attributes
        return parent::__get($key);
    }

    public function isOverdue(): bool {
        $due_date = $this->due_date;
        if (!$due_date) {
            return false;
        }
        return strtotime($due_date) < strtotime('today')
            && $this->status !== self::STATUS_COMPLETED;
    }

    public function isCompleted(): bool {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function markComplete(): int|false {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = current_time('mysql');
        return $this->save();
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'title' => $this->post_title,
            'description' => $this->post_content,
            'status' => $this->status ?: self::STATUS_PENDING,
            'priority' => $this->priority ?: self::PRIORITY_MEDIUM,
            'due_date' => $this->due_date,
            'is_overdue' => $this->isOverdue(),
            'is_completed' => $this->isCompleted(),
            'task_list_id' => $this->task_list_id,
            'created_at' => $this->post_date
        ];
    }
}
