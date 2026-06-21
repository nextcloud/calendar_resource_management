<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Console\Helper;

use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * The QuestionHelper class provides helpers to interact with the user.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class QuestionHelper extends Helper {
	/**
	 * Asks a question to the user.
	 *
	 * @return mixed The user answer
	 *
	 * @throws RuntimeException If there is no data to read in the input stream
	 */
	public function ask(InputInterface $input, OutputInterface $output, Question $question): mixed {
	}

	public function getName(): string {
	}

	/**
	 * Prevents usage of stty.
	 *
	 * @return void
	 */
	public static function disableStty() {
	}

	/**
	 * Outputs the question prompt.
	 *
	 * @return void
	 */
	protected function writePrompt(OutputInterface $output, Question $question) {
	}

	/**
	 * @return string[]
	 */
	protected function formatChoiceQuestionChoices(ChoiceQuestion $question, string $tag): array {
	}

	/**
	 * Outputs an error message.
	 *
	 * @return void
	 */
	protected function writeError(OutputInterface $output, \Exception $error) {
	}
}
