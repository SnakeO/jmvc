<?php
/**
 * JMVC CLSchedule Model
 *
 * Example model demonstrating JModelBase and ACFModelTrait usage.
 *
 * @package JMVC
 */

if (!defined('ABSPATH')) {
    exit;
}

class CLSchedule extends JModelBase
{
    use ACFModelTrait;

    /**
     * Post type for this model
     *
     * @var string
     */
    public static $post_type = 'cl_schedule';

    /**
     * Cached post object
     *
     * @var WP_Post|null
     */
    protected $post;

    /**
     * Constructor
     *
     * @param int|null $id Post ID
     */
    public function __construct($id = null)
    {
        parent::__construct($id);
    }

    /**
     * Generate post title
     *
     * @return string The post title
     */
    public function makePostTitle()
    {
        return 'Schedule ' . gmdate('Y-m-d H:i:s');
    }

    /**
     * Check if the schedule should execute now
     *
     * @return bool True if schedule should execute
     */
    public function shouldExecute()
    {
        $now = utc_time();

        // Date range check
        $start = strtotime($this->start_date);
        $end = strtotime($this->end_date);

        if ($start > $now || $end < $now) {
            return false;
        }

        // Time range check for the current day-of-week
        $dow_abbrevs = array(null, 'mon', 'tues', 'wed', 'thurs', 'fri', 'sat', 'sun');
        $dow_int = (int) utc_date()->format('N'); // 1 = mon, 7 = sun
        $dow = $dow_abbrevs[$dow_int] ?? null;

        if (!$dow) {
            return false;
        }

        $hours = $this->hoursOfOperation($dow);

        if (!$hours) {
            return false;
        }

        // Check if current time is within hours of operation
        $current_time = (int) utc_date()->format('Hi'); // HHMM format

        return $current_time >= (int) $hours['start_time'] && $current_time <= (int) $hours['end_time'];
    }

    /**
     * Get hours of operation for a given day of week
     *
     * @param string $dow Day of week abbreviation (mon, tues, etc.)
     * @return array|false Array with start_time and end_time, or false if not active
     */
    public function hoursOfOperation($dow)
    {
        $dow_is_active = $dow . '_is_active';
        $dow_start_time = $dow . '_start_time';
        $dow_end_time = $dow . '_end_time';

        if (!$this->$dow_is_active) {
            return false;
        }

        return array(
            'start_time' => $this->$dow_start_time,
            'end_time'   => $this->$dow_end_time,
        );
    }

    /**
     * Register the post type with Gutenberg support
     *
     * Call this from your theme's functions.php or plugin.
     */
    public static function register_post_type()
    {
        register_post_type(self::$post_type, array(
            'labels'       => array(
                'name'          => 'Schedules',
                'singular_name' => 'Schedule',
            ),
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true, // Enable Gutenberg and REST API
            'supports'     => array('title', 'editor', 'custom-fields'),
            'has_archive'  => false,
        ));
    }
}
