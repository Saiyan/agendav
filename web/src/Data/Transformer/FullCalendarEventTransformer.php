<?php

namespace AgenDAV\Data\Transformer;

/*
 * Copyright 2014-2015 Jorge López Pérez <jorge@adobo.org>
 *
 *  This file is part of AgenDAV.
 *
 *  AgenDAV is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  any later version.
 *
 *  AgenDAV is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with AgenDAV.  If not, see <http://www.gnu.org/licenses/>.
 */

use League\Fractal;
use AgenDAV\EventInstance;
use AgenDAV\CalDAV\Resource\Calendar;
use AgenDAV\Event\FullCalendarEvent;

class FullCalendarEventTransformer extends Fractal\TransformerAbstract
{

    /**
     * Timezone the events will be converted to
     *
     * @var \DateTimeZone
     */
    protected $timezone;

    /**
     * Creates a new transformer, specifying the timezone Fullcalendar
     * is configured to receive the events
     *
     * @param \DateTimeZone $timezone
     */
    public function __construct(\DateTimeZone $timezone)
    {
        $this->timezone = $timezone;
    }


    public function transform(FullCalendarEvent $fc_event)
    {
        $event = $fc_event->getEvent();
        $start = $event->getStart()->setTimeZone($this->timezone);
        $end = $event->getEnd()->setTimeZone($this->timezone);

        $result = [
            'calendar' => $fc_event->getCalendarUrl(),
            'href' => $fc_event->getUrl(),
            'etag' => $fc_event->getEtag(),
            'uid' => $event->getUid(),
            'title' => $event->getSummary(),
            'start' => $start->format('c'),
            'end' => $end->format('c'),
            'allDay' => $event->isAllDay(),
            // TODO think about going without orig_allday
            'orig_allday' => $event->isAllDay(),
            'location' => $event->getLocation(),
            'icalendar_class' => $event->getClass(),
            'transp' => $event->getTransp(),
            'description' => $event->getDescription(),
        ];

        $result['id'] = $result['calendar'] . $result['uid'];

        if ($event->isRecurrent()) {
            $result['rrule'] = $event->getRepeatRule();
            $result['recurrence_id'] = $event->getRecurrenceId();

            // Append RECURRENCE-ID to generated id
            $result['id'] .= '@' . $result['recurrence_id'];
        }

        // Reminders
        $result['visible_reminders'] = [];
        $result['reminders'] = [];

        $reminders = $event->getReminders();
        foreach ($reminders as $reminder) {
            list($count, $unit) = $reminder->getParsedWhen();
            $result['visible_reminders'][] = $reminder->getPosition();
            $result['reminders'][] = [
                'position' => $reminder->getPosition(),
                'count' => $count,
                'unit' => $unit,
            ];
        }

        return $result;
    }
}
