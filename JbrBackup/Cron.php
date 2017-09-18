<?php

#
# A more friendly interface to WordPress Cron
#

namespace JbrBackup;

defined('ABSPATH') or die();

class Cron {

    # -------------------------------------------------------------------------
    # Callbacks
    # -------------------------------------------------------------------------
    
    #
    # "Quasi-callback" created by me :-}
    #
    
    public static function OnLoad() {
        add_filter('cron_schedules', ['JbrBackup\Cron', 'AddCronSchedules']);
    }

    #
    # Define new schedule frequencies
    #
    
    public static function AddCronSchedules($schedules) {
        $secsInDay = 24 * 60 * 60;
        $schedules['weekly'] = [
            'interval' => 7 * $secsInDay,
            'display' => 'Weekly'
        ];
        $schedules['biweekly'] = [
            'interval' => 14 * $secsInDay,
            'display' => 'Biweekly'
        ];
        
        return $schedules;
    }
    
    # -------------------------------------------------------------------------
    # Public API
    # -------------------------------------------------------------------------
    
    public static function ListenToEvent($actionName, $callback) {
        add_action($actionName, $callback, 10, 2);
    }
    
    #
    # Clear all $actionName events. (Unscheduling all events doesn't do it.)
    #
    
    public static function ClearEvent($actionName, $args=[]) {
        wp_clear_scheduled_hook($actionName, ['manual', $args]);
        wp_clear_scheduled_hook($actionName, ['scheduled', $args]);
    }
    
    public static function ScheduleJobNow($actionName, $args=[]) {
        # If a job of this type is scheduled, unschedule it
        $stamp = wp_next_scheduled($actionName, ['scheduled', $args]);
        
        $freq = '';
        if ($stamp) {
            $freq = wp_get_schedule($actionName, ['scheduled', $args]);
            wp_unschedule_event($stamp, $actionName, ['scheduled', $args]);
        }

        # Schedule a job now
        wp_schedule_single_event(time(), $actionName, ['manual', $args]);
        
        # If we unscheduled one, reschedule it
        if ($stamp) {
            self::ScheduleRecurringJob($freq, $actionName, $args);
        }
    }
    
    #
    # Schedule a job to run with the specified $frequency.
    # Unlike normal WP Cron behavior, do *not* run it immediately.
    #
    
    public static function ScheduleRecurringJob($frequency, $actionName, $args=[]) {
        # If schedule exists, remove it
        $stamp = self::GetNextScheduledJob($actionName, $args);
        if ($stamp)
            wp_unschedule_event($stamp, $actionName, ['scheduled', $args]);
        
        # Make a timestamp in the future
        $addAmount = [
            'hourly' => '1 hour',
            'daily' => '1 day',
            'weekly' => '1 week',
            'biweekly' => '2 weeks'
        ];
        $futureStamp = strtotime($addAmount[$frequency], time());
        
        # Schedule the job for sometime in the future
        wp_schedule_event($futureStamp, $frequency, $actionName, ['scheduled', $args]);
        
        return $stamp != false;  # ??
    }
    
    public static function UnscheduleRecurringJob($actionName, $args=[]) {
        $stamp = self::GetNextScheduledJob($actionName, $args);
        if ($stamp) {
            wp_unschedule_event($stamp, $actionName, ['scheduled', $args]);
        }
        
        return $stamp != false;  # ??
    }

    public static function GetNextScheduledJob($actionName, $args=[]) {
        $stamp = wp_next_scheduled($actionName, ['scheduled', $args]);
        
        return $stamp;
    }
   
}

Cron::OnLoad();

#?>