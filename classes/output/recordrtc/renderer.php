<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The override qtype_recordrtc_renderer for the quiz_answersheets module.
 *
 * @package   quiz_answersheets
 * @copyright 2020 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets\output\recordrtc;

defined('MOODLE_INTERNAL') || die();

use DOMDocument;
use DOMXPath;
use html_writer;
use question_attempt;
use question_display_options;

// Work-around when the class does not exist.
if (class_exists('\qtype_recordrtc_renderer')) {
    class_alias('\qtype_recordrtc_renderer', '\qtype_recordrtc_renderer_alias');
    require_once($CFG->dirroot . '/question/type/recordrtc/renderer.php');
} else {
    class_alias('\qtype_renderer', '\qtype_recordrtc_renderer_alias');
}

/**
 * The override qtype_recordrtc_renderer for the quiz_answersheets module.
 *
 * @copyright  2020 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class qtype_recordrtc_override_renderer extends \qtype_recordrtc_renderer_alias {

    protected function no_recording_message(): string {
        $output = '';

        // Add custom class to original no recording message div, so we can control it by CSS selector easily.
        $output .= html_writer::div(parent::no_recording_message(), 'no-recording-warning');
        $output .= $this->no_response_recorded();

        return $output;
    }

    protected function playback_ui($recordingurl, string $mediatype, string $filename, $videowidth, $videoheight): string {
        $output = '';

        // Add custom class to original playback ui div, so we can control it by CSS selector easily.
        $output .= html_writer::div(parent::playback_ui($recordingurl, $mediatype, $filename, $videowidth, $videoheight),
                'playback-ui-warning');
        $output .= $this->response_recorded($filename);

        return $output;
    }

    /**
     * Render a message to say that no response recorded.
     *
     * @return string HTML to output.
     */
    protected function no_response_recorded(): string {
        return html_writer::div(get_string('no_response_recorded', 'quiz_answersheets'), 'interactive-content-warning');
    }

    /**
     * Render a message to say that interactive content is not available in this format.
     *
     * @return string HTML to output.
     */
    protected function interactive_content_warning(): string {
        return html_writer::div(get_string('interactive_content_warning', 'quiz_answersheets'), 'interactive-content-warning');
    }

    /**
     * Render a message to say that response recorded.
     *
     * @param string $filename
     * @return string HTML to output.
     */
    protected function response_recorded(string $filename): string {
        return html_writer::div(get_string('response_recorded', 'quiz_answersheets', $filename), 'interactive-content-warning');
    }

    protected function general_feedback(question_attempt $qa) {
        $generalfeedback = $qa->get_question()->generalfeedback;

        if (!empty($generalfeedback) && $qa->get_question()->generalfeedbackformat == FORMAT_HTML) {
            $qa->get_question()->generalfeedback = $this->process_feedback_interactive_content($generalfeedback);
        }

        return parent::general_feedback($qa);
    }

    /**
     * Process the interactive content for given feedback.
     *
     * @param string $feedback Feedback content
     * @return string HTML content
     */
    private function process_feedback_interactive_content(string $feedback): string {
        $doc = new DOMDocument;
        libxml_use_internal_errors(true);
        $doc->loadHTML(mb_convert_encoding($feedback, 'HTML-ENTITIES', 'UTF-8'));
        libxml_clear_errors();
        $finder = new DomXPath($doc);

        $interactiveelements = [];

        $audioelements = $doc->getElementsByTagName('audio');
        $videoselements = $doc->getElementsByTagName('video');
        $mediacollectionelements = $finder->query("//a[contains(concat(' ', normalize-space(@class), ' '), 'atto_oulinktofile')]");

        $interactiveelements = array_merge($interactiveelements, iterator_to_array($audioelements));
        $interactiveelements = array_merge($interactiveelements, iterator_to_array($videoselements));
        $interactiveelements = array_merge($interactiveelements, iterator_to_array($mediacollectionelements));

        foreach ($interactiveelements as $interactiveelement) {
            $fragment = $doc->createDocumentFragment();
            $fragment->appendXML($this->interactive_content_warning());
            if ($interactiveelement->nodeName == 'audio' || $interactiveelement->nodeName == 'video') {
                $interactiveelement->setAttribute('class', 'interactive-media-player');
            }
            $interactiveelement->parentNode->appendChild($fragment);
        }

        return $doc->saveHTML();
    }

}
