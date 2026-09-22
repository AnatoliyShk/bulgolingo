<script setup>
import '@/assets/scss/components/tutor-bot.scss'
import { computed, nextTick, ref, onBeforeUnmount } from 'vue'

// How many turns travel back to the server with each question. The endpoint
// caps this at six, and every replayed turn is paid for again, so the widget
// sends the tail of the conversation rather than all of it.
const HISTORY_TURNS = 6

const open = ref(false)
const question = ref('')
const turns = ref([])
const streaming = ref(false)
const error = ref('')
const thread = ref(null)
const field = ref(null)

let controller = null

const suggestions = [
    'How do I say thank you?',
    'What should I learn first?',
    'Is Bulgarian hard for English speakers?',
]

const canSend = computed(() => question.value.trim().length >= 2 && !streaming.value)

// The layout carries no csrf-token meta tag, so the token comes from the
// XSRF-TOKEN cookie Laravel sets on every web response — the same one axios
// reads for the rest of the app. It arrives url-encoded.
function csrfToken() {
    const cookie = document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))

    return cookie ? decodeURIComponent(cookie.slice('XSRF-TOKEN='.length)) : ''
}

function toggle() {
    open.value = !open.value

    if (open.value) {
        nextTick(() => field.value?.focus())
    }
}

function scrollToEnd() {
    nextTick(() => {
        if (thread.value) {
            thread.value.scrollTop = thread.value.scrollHeight
        }
    })
}

function ask(text) {
    question.value = text
    send()
}

// Reads the SSE body by hand rather than with EventSource, which cannot POST.
// Frames arrive as an "event: <name>" line and a "data: <json>" line separated
// by a blank line, and the payload is JSON so a delta spanning several lines
// still travels on one. The closing "</stream>" sentinel is the one frame that
// is not JSON.
async function send() {
    if (!canSend.value) return

    const asked = question.value.trim()

    question.value = ''
    error.value = ''
    turns.value.push({ role: 'user', content: asked })
    turns.value.push({ role: 'assistant', content: '' })
    streaming.value = true
    scrollToEnd()

    const reply = turns.value[turns.value.length - 1]
    controller = new AbortController()

    try {
        const response = await fetch('/tutor', {
            method: 'POST',
            signal: controller.signal,
            headers: {
                'Content-Type': 'application/json',
                Accept: 'text/event-stream',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': csrfToken(),
            },
            body: JSON.stringify({
                question: asked,
                history: turns.value.slice(0, -2).slice(-HISTORY_TURNS),
            }),
        })

        if (!response.ok) {
            throw new Error(response.status === 429
                ? 'That is a lot of questions at once. Give it a minute and try again.'
                : 'The tutor is unavailable right now.')
        }

        const reader = response.body.getReader()
        const decoder = new TextDecoder()
        let buffer = ''

        for (;;) {
            const { done, value } = await reader.read()
            if (done) break

            buffer += decoder.decode(value, { stream: true })

            const frames = buffer.split('\n\n')
            buffer = frames.pop() ?? ''

            for (const frame of frames) {
                let name = 'update'
                let data = ''

                for (const line of frame.split('\n')) {
                    if (line.startsWith('event: ')) name = line.slice(7)
                    if (line.startsWith('data: ')) data += line.slice(6)
                }

                if (data === '' || data === '</stream>') continue

                const payload = JSON.parse(data)

                if (name === 'error') {
                    throw new Error(payload.message)
                }

                reply.content += payload.delta
                scrollToEnd()
            }
        }

        if (reply.content === '') {
            throw new Error('The tutor had nothing to say. Try asking another way.')
        }
    } catch (e) {
        if (e.name !== 'AbortError') {
            error.value = e.message
            turns.value.pop()
        }
    } finally {
        streaming.value = false
        controller = null
        scrollToEnd()
    }
}

onBeforeUnmount(() => controller?.abort())
</script>

<template>
    <div class="nb-tutor">
        <Transition name="nb-tutor-panel">
            <section v-if="open" id="nb-tutor-panel" class="nb-tutor__panel" aria-label="Ask the tutor">
                <header class="nb-tutor__head">
                    <span class="nb-tutor__avatar" aria-hidden="true">БГ</span>
                    <div class="nb-tutor__heading">
                        <h2 class="nb-tutor__title">Питай ме</h2>
                        <span class="nb-tutor__en">Ask me about Bulgarian</span>
                    </div>
                    <button type="button" class="nb-tutor__close" aria-label="Close tutor" @click="toggle">✕</button>
                </header>

                <div ref="thread" class="nb-tutor__thread">
                    <div v-if="turns.length === 0" class="nb-tutor__empty">
                        <p class="nb-tutor__empty-text">
                            A quick question about Bulgarian? Ask away — or start with one of these.
                        </p>
                        <ul class="nb-tutor__suggestions">
                            <li v-for="s in suggestions" :key="s">
                                <button type="button" class="nb-tutor__suggestion" @click="ask(s)">{{ s }}</button>
                            </li>
                        </ul>
                    </div>

                    <p
                        v-for="(turn, i) in turns"
                        :key="i"
                        class="nb-tutor__turn"
                        :class="`nb-tutor__turn--${turn.role}`"
                    >
                        {{ turn.content
                        }}<span
                            v-if="turn.role === 'assistant' && streaming && i === turns.length - 1"
                            class="nb-tutor__caret"
                            aria-hidden="true"
                        />
                    </p>

                    <p v-if="error" class="nb-tutor__error" role="alert">{{ error }}</p>
                </div>

                <form class="nb-tutor__form" @submit.prevent="send">
                    <label class="nb-tutor__label" for="nb-tutor-field">Your question</label>
                    <input
                        id="nb-tutor-field"
                        ref="field"
                        v-model="question"
                        type="text"
                        maxlength="500"
                        autocomplete="off"
                        class="nb-tutor__field"
                        placeholder="How do I say hello?"
                    />
                    <button type="submit" class="nb-tutor__send" :disabled="!canSend">
                        {{ streaming ? '…' : 'Send' }}
                    </button>
                </form>
            </section>
        </Transition>

        <button
            type="button"
            class="nb-tutor__launcher"
            :class="{ 'nb-tutor__launcher--open': open }"
            :aria-expanded="open"
            aria-controls="nb-tutor-panel"
            @click="toggle"
        >
            <span class="nb-tutor__launcher-text" lang="bg">Питай</span>
            <span class="nb-tutor__launcher-en">Ask</span>
        </button>
    </div>
</template>
