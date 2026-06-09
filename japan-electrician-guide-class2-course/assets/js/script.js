(function () {
	'use strict';

	function normalize(value) {
		return String(value || '').toLowerCase().trim();
	}

	function initCourseFiltering(wrapper) {
		var searchInput = wrapper.querySelector('[data-jeg-course-search]');
		var filterButtons = wrapper.querySelectorAll('.jeg-filter-row .jeg-filter-button');
		var cards = wrapper.querySelectorAll('[data-course-card]');
		var activeFilter = 'all';

		function applyFilters() {
			var query = normalize(searchInput ? searchInput.value : '');

			cards.forEach(function (card) {
				var title = normalize(card.getAttribute('data-title'));
				var excerpt = normalize(card.getAttribute('data-excerpt'));
				var category = normalize(card.getAttribute('data-category'));
				var matchesSearch = !query || title.indexOf(query) !== -1 || excerpt.indexOf(query) !== -1 || category.indexOf(query) !== -1;
				var matchesFilter = activeFilter === 'all' || category.indexOf(activeFilter) !== -1;
				card.classList.toggle('is-hidden', !(matchesSearch && matchesFilter));
			});
		}

		if (searchInput) {
			searchInput.addEventListener('input', applyFilters);
		}

		filterButtons.forEach(function (button) {
			button.addEventListener('click', function () {
				activeFilter = button.getAttribute('data-filter') || 'all';
				filterButtons.forEach(function (candidate) {
					candidate.classList.toggle('is-active', candidate === button);
				});
				applyFilters();
			});
		});

		applyFilters();
	}

	function initLessonSelection(wrapper) {
		var links = wrapper.querySelectorAll('[data-jeg-open-lesson]');
		var cards = wrapper.querySelectorAll('[data-course-card][data-lesson-id]');
		var panels = wrapper.querySelectorAll('[data-lesson-detail]');
		var initialLesson = Number(wrapper.getAttribute('data-initial-lesson') || 0);

		if (!links.length || !panels.length) {
			return;
		}

		function selectLesson(lessonId, updateHash) {
			if (!lessonId) {
				return;
			}

			cards.forEach(function (card) {
				card.classList.toggle('is-active', Number(card.getAttribute('data-lesson-id')) === lessonId);
			});

			panels.forEach(function (panel) {
				panel.classList.toggle('is-hidden', Number(panel.getAttribute('data-lesson-detail')) !== lessonId);
			});

			if (updateHash) {
				window.location.hash = 'jeg-lesson-' + lessonId;
			}
		}

		links.forEach(function (link) {
			link.addEventListener('click', function (event) {
				event.preventDefault();
				var lessonId = Number(link.getAttribute('data-jeg-open-lesson'));
				selectLesson(lessonId, true);
			});
		});

		var hash = window.location.hash || '';
		var hashMatch = hash.match(/^#jeg-lesson-(\d+)$/);
		var hashLessonId = hashMatch ? Number(hashMatch[1]) : 0;
		selectLesson(hashLessonId || initialLesson, false);
	}

	function initVocabFiltering(wrapper) {
		var searchInput = wrapper.querySelector('[data-jeg-vocab-search]');
		var rows = wrapper.querySelectorAll('[data-vocab-row]');

		if (!searchInput) {
			return;
		}

		searchInput.addEventListener('input', function () {
			var query = normalize(searchInput.value);

			rows.forEach(function (row) {
				var haystack = normalize(row.getAttribute('data-search') || row.textContent);
				row.classList.toggle('is-hidden', query && haystack.indexOf(query) === -1);
			});
		});
	}

	function initQuiz(wrapper) {
		var scoreNode = wrapper.querySelector('[data-jeg-quiz-score] strong');
		var restartButton = wrapper.querySelector('[data-jeg-quiz-restart]');
		var questions = Array.prototype.slice.call(wrapper.querySelectorAll('[data-question]'));
		var score = 0;
		var answered = new WeakSet();

		function updateScore() {
			if (scoreNode) {
				scoreNode.textContent = score + ' / ' + questions.length;
			}
		}

		function resetQuestion(question) {
			answered.delete(question);
			question.querySelectorAll('.jeg-quiz-choice').forEach(function (choice) {
				choice.classList.remove('is-correct', 'is-wrong');
				choice.disabled = false;
			});
			var answerNode = question.querySelector('[data-quiz-answer]');
			var explanationNode = question.querySelector('[data-quiz-explanation]');
			if (answerNode) {
				answerNode.textContent = '';
				answerNode.classList.add('is-hidden');
			}
			if (explanationNode) {
				explanationNode.classList.add('is-hidden');
			}
		}

		questions.forEach(function (question) {
			var correctIndex = Number(question.getAttribute('data-correct'));
			var answerNode = question.querySelector('[data-quiz-answer]');
			var explanationNode = question.querySelector('[data-quiz-explanation]');

			question.querySelectorAll('.jeg-quiz-choice').forEach(function (choice) {
				choice.addEventListener('click', function () {
					if (answered.has(question)) {
						return;
					}

					var selectedIndex = Number(choice.getAttribute('data-choice-index'));
					var isCorrect = selectedIndex === correctIndex;
					answered.add(question);

					question.querySelectorAll('.jeg-quiz-choice').forEach(function (button) {
						button.disabled = true;
						var buttonIndex = Number(button.getAttribute('data-choice-index'));
						if (buttonIndex === correctIndex) {
							button.classList.add('is-correct');
						} else if (button === choice && !isCorrect) {
							button.classList.add('is-wrong');
						}
					});

					if (isCorrect) {
						score += 1;
					}

					if (answerNode) {
						answerNode.textContent = isCorrect ? 'Correct: well done.' : 'Incorrect: review the explanation below.';
						answerNode.classList.remove('is-hidden');
					}

					if (explanationNode) {
						explanationNode.classList.remove('is-hidden');
					}

					updateScore();
				});
			});
		});

		if (restartButton) {
			restartButton.addEventListener('click', function () {
				score = 0;
				questions.forEach(resetQuestion);
				updateScore();
				wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
			});
		}

		updateScore();
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('[data-jeg-course]').forEach(function (wrapper) {
			initCourseFiltering(wrapper);
			initLessonSelection(wrapper);
		});
		document.querySelectorAll('[data-jeg-vocab]').forEach(initVocabFiltering);
		document.querySelectorAll('[data-jeg-quiz]').forEach(initQuiz);
	});
}());