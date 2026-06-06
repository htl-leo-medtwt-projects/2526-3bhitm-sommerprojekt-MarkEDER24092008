/**
 * Exercise page logic (classic MVP)
 * - Fetches current hearts + next exercise for user's language + mode
 * - Renders question (multiple choice or text)
 * - Submits answer, deducts hearts on mistakes, advances to next exercise
 */

function getUrlParam(name) {
  const url = new URL(window.location.href);
  return url.searchParams.get(name);
}

const state = {
  mode: null,
  currentExercise: null,
  hearts: 5,
  lesson: null,
  remainingCount: 0,
};

function setFeedback(msg, isError = false) {
  const el = document.getElementById('feedback');
  if (!el) return;
  el.textContent = msg;
  el.style.color = isError ? '#d32f2f' : '';
}

function setHearts(count) {
  state.hearts = count;
  const heartsCountEl = document.getElementById('hearts-count');
  if (heartsCountEl) heartsCountEl.textContent = String(count);

  const submitBtn = document.getElementById('submit-btn');
  const skipBtn = document.getElementById('skip-btn');

  const disabled = count <= 0;
  if (submitBtn) submitBtn.disabled = disabled;
  if (skipBtn) skipBtn.disabled = disabled;
}

function showMC(choices) {
  document.getElementById('mc-choices').style.display = 'block';
  document.getElementById('text-input-wrap').style.display = 'none';

  const mcWrap = document.getElementById('mc-choices');
  mcWrap.innerHTML = '';

  const frag = document.createDocumentFragment();

  choices.forEach((choiceText, idx) => {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'exercise-btn secondary';
    btn.textContent = choiceText;
    btn.dataset.choiceIndex = String(idx);

    btn.addEventListener('click', () => {
      // mark selection
      [...mcWrap.querySelectorAll('button')].forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      state.selectedAnswer = choiceText;
    });

    frag.appendChild(btn);
  });

  // basic layout: 1 column (fits mobile). If needed, can be CSS grid later.
  mcWrap.appendChild(frag);
}

function showTextInput() {
  document.getElementById('mc-choices').style.display = 'none';
  document.getElementById('text-input-wrap').style.display = 'block';
  state.selectedAnswer = null;

  const input = document.getElementById('text-answer');
  if (input) input.value = '';
  if (input) input.focus();
}

function renderExercise(ex) {
  state.currentExercise = ex;

  document.getElementById('lesson-title').textContent = state.lesson?.title || 'Lesson';
  document.getElementById('exercise-mode').textContent = state.mode;

  document.getElementById('question-text').textContent = ex.question_text || '';

  const hintEl = document.getElementById('question-hint');
  if (hintEl) {
    const hint = ex.hint;
    if (hint) {
      hintEl.style.display = 'block';
      hintEl.textContent = hint;
    } else {
      hintEl.style.display = 'none';
      hintEl.textContent = '';
    }
  }

  const imgEl = document.getElementById('question-image');
  if (imgEl) {
    if (ex.image_url) {
      imgEl.src = ex.image_url;
      imgEl.style.display = 'block';
    } else {
      imgEl.style.display = 'none';
      imgEl.src = '';
    }
  }

  const audioEl = document.getElementById('question-audio');
  if (audioEl) {
    if (ex.audio_url) {
      audioEl.src = ex.audio_url;
      audioEl.style.display = 'block';
    } else {
      audioEl.style.display = 'none';
      audioEl.src = '';
    }
  }

  // Mode-specific UI
  if (state.mode === 'multiple_choice') {
    // Server should send choices for MC mode
    showMC(ex.choices || []);
    state.selectedAnswer = null;
  } else {
    showTextInput();
  }

  // if no hearts, block actions
  setHearts(state.hearts);
}

async function refillHeartsAndUpdateFromServer() {
  const res = await fetch('./sql/exercise_get.php?mode=' + encodeURIComponent(state.mode || 'multiple_choice'), {
    method: 'GET'
  });

  if (!res.ok) throw new Error('HTTP ' + res.status);

  const data = await res.json();
  if (!data.success) throw new Error(data.error || 'Failed to load exercise');

  state.hearts = data.hearts;
  state.lesson = data.lesson;
  state.remainingCount = data.remainingCount;

  setHearts(state.hearts);
}

async function loadExercise() {
  const mode = getUrlParam('mode');
  state.mode = mode;

  // Fetch
  const res = await fetch('./sql/exercise_get.php?mode=' + encodeURIComponent(mode), { method: 'GET' });
  if (!res.ok) throw new Error('HTTP ' + res.status);

  const data = await res.json();
  if (!data.success) throw new Error(data.error || 'Failed to load exercise');

  state.hearts = data.hearts;
  state.lesson = data.lesson;
  state.remainingCount = data.remainingCount;

  setHearts(state.hearts);
  if (data.completed) {
    setFeedback('Lesson completed! Returning to Home...', false);
    setTimeout(() => window.location.href = './home.html', 900);
    return;
  }

  renderExercise(data.exercise);
}

async function submitAnswer() {
  if (!state.currentExercise) return;

  const submitBtn = document.getElementById('submit-btn');
  if (submitBtn) submitBtn.disabled = true;

  try {
    const payload = {
      exercise_id: state.currentExercise.id,
      answer_given: null,
    };

    if (state.mode === 'multiple_choice') {
      payload.answer_given = state.selectedAnswer || '';
      if (!payload.answer_given) {
        setFeedback('Select an answer first.', true);
        if (submitBtn) submitBtn.disabled = false;
        return;
      }
    } else {
      const input = document.getElementById('text-answer');
      payload.answer_given = (input?.value || '').trim();
      if (!payload.answer_given) {
        setFeedback('Type your answer first.', true);
        if (submitBtn) submitBtn.disabled = false;
        return;
      }
    }

    const res = await fetch('./sql/exercise_submit.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });

    if (!res.ok) throw new Error('HTTP ' + res.status);

    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Submit failed');

    setHearts(data.hearts);

    if (data.is_correct) {
      setFeedback('✅ Correct!', false);
    } else {
      setFeedback('❌ Wrong answer. -1 heart', true);
    }

    if (data.completed) {
      setFeedback('🎉 Lesson completed! Returning to Home...', false);
      setTimeout(() => window.location.href = './home.html', 900);
      return;
    }

    // Next exercise
    state.currentExercise = null;
    setFeedback('');
    renderExercise(data.next_exercise);

  } catch (e) {
    setFeedback(String(e.message || e), true);
  } finally {
    if (submitBtn) submitBtn.disabled = false;
  }
}

async function init() {
  try {
    await loadExercise();
  } catch (e) {
    console.error(e);
    setFeedback('Failed to load exercise: ' + (e.message || e), true);
  }

  const submitBtn = document.getElementById('submit-btn');
  const skipBtn = document.getElementById('skip-btn');

  if (submitBtn) submitBtn.addEventListener('click', submitAnswer);

  // MVP: Skip behaves like "do nothing, load again"
  if (skipBtn) {
    skipBtn.addEventListener('click', async () => {
      if ((state.hearts || 0) <= 0) {
        setFeedback('No hearts left. Come back in a while to refill.', true);
        return;
      }
      try {
        const res = await fetch('./sql/exercise_get.php?mode=' + encodeURIComponent(state.mode || ''), { method: 'GET' });
        const data = await res.json();
        if (data.success && !data.completed) renderExercise(data.exercise);
      } catch (e) {
        console.error(e);
      }
    });
  }
}

document.addEventListener('DOMContentLoaded', init);
