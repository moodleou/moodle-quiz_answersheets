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
 * Tests for the quiz answer sheet report.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quiz_answersheets;

/**
 * Tests for the quiz answer sheet report.
 *
 * @package   quiz_answersheets
 * @copyright 2019 The Open University
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \quiz_answersheets\utils::get_question_instruction
 */
final class report_test extends \advanced_testcase {

    /**
     * Test get_question_instruction function.
     *
     * @dataProvider get_question_instruction_cases
     * @param string $questiontype Question type name.
     * @param string $expectedinstruction Expected instruction for question.
     */
    public function test_get_question_instruction(string $questiontype, string $expectedinstruction): void {
        $this->resetAfterTest();
        if (\question_bank::is_qtype_installed($questiontype)) {
            $instruction = \quiz_answersheets\utils::get_question_instruction($questiontype);
            $this->assert_same_instruction($expectedinstruction, $instruction);
        } else {
            $this->markTestSkipped();
        }
    }

    /**
     * Test case for test_get_question_instruction
     *
     * @return array List of test cases
     */
    public static function get_question_instruction_cases(): array {
        return [
            [
                'coderunner',
                'Write your answer in the space provided.',
            ],
            [
                'ddwtos',
                'Write the letter of the corresponding answer (A, B, C, D, ...) in the space provided.
After the item is the maximum number of times it can be used. e.g. (1) means that the item can be used once, ' .
                '(2) means twice etc. An asterisk (*) means that the items’ use is unlimited.',
            ],
            [
                'ddmarker',
                'Mark the points on the image and write the letter of corresponding answer (A, B, C, D, …) beside them.
After the item is the maximum number of times it can be used. e.g. (1) means that the item can be used once, ' .
                '(2) means twice etc. An asterisk (*) means that the items’ use is unlimited.',
            ],
            [
                'ddimageortext',
                'Mark the points on the image and write the letter of corresponding answer (A, B, C, D, …) beside them.
After the item is the maximum number of times it can be used. e.g. (1) means that the item can be used once, ' .
                '(2) means twice etc. An asterisk (*) means that the items’ use is unlimited.',
            ],
            [
                'essay',
                'Write your answer in the space provided.',
            ],
            [
                'match',
                'Write the letter of the corresponding answer (A, B, C, D, ...) in the space provided.',
            ],
            [
                'multichoice',
                'Select the correct answer.',
            ],
            [
                'numerical',
                'Write your answer (in numerical value) in the space provided.',
            ],
            [
                'ordering',
                'Write the correct order in the space provided.',
            ],
            [
                'oumultiresponse',
                'Select the correct answer(s).',
            ],
            [
                'pmatch',
                'Write your answer in the space provided. Please keep it to a sentence or two.',
            ],
            [
                'pmatchjme',
                'Write your answer in the space provided.',
            ],
            [
                'gapselect',
                'Write the letter of the corresponding answer (A, B, C, D, ...) in the space provided.',
            ],
            [
                'shortanswer',
                'Write your answer in the space provided. Please keep it to a sentence or two.',
            ],
            [
                'stack',
                'Write your answer in the space provided.',
            ],
            [
                'truefalse',
                'Select the correct answer.',
            ],
            [
                'varnumeric',
                'Write your answer in the space provided.',
            ],
            [
                'varnumericset',
                'Write your answer in the space provided.',
            ],
            [
                'varnumunit',
                'Write your answer in the space provided.',
            ],
            [
                'wordselect',
                'Select the answer(s) by circling the key word(s).',
            ],
        ];
    }

    /**
     * Replace line break and assert the instruction
     *
     * @param string $expectedinstrction Expected instruction
     * @param string $instruction Actual instruction
     */
    private function assert_same_instruction(string $expectedinstrction, string $instruction): void {
        $this->assertEquals(str_replace("\r\n", "\n", $expectedinstrction), str_replace("\r\n", "\n", $instruction));
    }
}
