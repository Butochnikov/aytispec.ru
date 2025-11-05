import './bootstrap';

const pendingVisitors = [];
let feedContainer = null;
const activeVisitors = new Map();
let summaryContainer = null;
let summaryCountNode = null;
let visitorTtlMs = 2 * 60 * 1000; // 2 минуты по умолчанию

const ensureFeedContainer = () => {
    if (feedContainer) {
        return feedContainer;
    }

    const container = document.createElement('div');
    container.className = 'visitor-feed';
    container.setAttribute('aria-live', 'polite');
    container.setAttribute('aria-atomic', 'false');

    feedContainer = container;
    document.body.appendChild(container);

    return container;
};

const ensureSummary = () => {
    if (summaryContainer) {
        return summaryContainer;
    }

    const wrapper = document.createElement('div');
    wrapper.className = 'visitor-feed__summary';

    const labelNode = document.createElement('span');
    labelNode.className = 'visitor-feed__summary-label';
    labelNode.textContent = 'Сейчас в сети:';

    const countNode = document.createElement('span');
    countNode.className = 'visitor-feed__summary-count';
    countNode.textContent = '0';

    wrapper.appendChild(labelNode);
    wrapper.appendChild(countNode);

    summaryContainer = wrapper;
    summaryCountNode = countNode;
    document.body.appendChild(wrapper);

    return wrapper;
};

const bootstrapInitialVisitors = async () => {
    ensureSummary();

    try {
        const response = await fetch('/api/visitors/online', {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        if (! response.ok) {
            throw new Error(`Unexpected status ${response.status}`);
        }

        const payload = await response.json();

        if (typeof payload.ttl === 'number' && payload.ttl > 0) {
            visitorTtlMs = payload.ttl * 1000;
        }

        activeVisitors.clear();

        if (Array.isArray(payload.visitors)) {
            payload.visitors.forEach((visitor) => {
                if (! visitor?.id) {
                    return;
                }

                const parsedTimestamp = visitor.last_seen ? Date.parse(visitor.last_seen) : Date.now();

                activeVisitors.set(visitor.id, {
                    seenAt: Number.isNaN(parsedTimestamp) ? Date.now() : parsedTimestamp,
                    label: visitor.label ?? 'Гость',
                });
            });
        }

        updateSummary();
    } catch (error) {
        console.warn('Не удалось получить список активных гостей', error);
    }
};

const createBubble = (visitor) => {
    const bubble = document.createElement('div');
    bubble.className = 'visitor-feed__bubble visitor-feed__bubble--emoji';
    bubble.title = visitor.label ?? 'Visitor';
    bubble.dataset.visitorId = visitor.id ?? crypto.randomUUID?.() ?? Date.now().toString();

    if (visitor.avatarUrl) {
        const image = document.createElement('img');
        image.src = visitor.avatarUrl;
        image.alt = visitor.label ?? 'Visitor';
        bubble.appendChild(image);
    } else {
        const text = document.createElement('span');
        text.className = 'visitor-feed__initial';
        text.textContent = '👨‍💻';
        bubble.appendChild(text);
    }

    bubble.addEventListener('animationend', () => {
        bubble.remove();
    });

    return bubble;
};

const showVisitor = (visitor) => {
    const container = ensureFeedContainer();

    const bubble = createBubble(visitor);
    container.appendChild(bubble);

    window.setTimeout(() => {
        if (bubble.isConnected) {
            bubble.remove();
        }
    }, 4000);
};

const flushQueue = () => {
    while (pendingVisitors.length > 0) {
        const visitor = pendingVisitors.shift();
        showVisitor(visitor);
    }
};

const scheduleVisitor = (visitor) => {
    pendingVisitors.push(visitor);

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        flushQueue();
    }

    trackVisitor(visitor);
};

window.addEventListener('DOMContentLoaded', () => {
    if (pendingVisitors.length > 0) {
        flushQueue();
    }

    ensureSummary();
    bootstrapInitialVisitors();
});

if (window.Echo) {
    window.Echo.channel('visitors').listen('.visitor.entered', (event) => {
        scheduleVisitor(event);
    });
} else {
    console.warn(
        'Echo instance is not available. Live visitor feed will be disabled until Reverb is configured.',
    );
}

const trackVisitor = (visitor) => {
    if (!visitor?.id) {
        return;
    }

    activeVisitors.set(visitor.id, {
        seenAt: Date.now(),
        label: visitor.label ?? 'Гость',
    });

    updateSummary();
};

const updateSummary = () => {
    ensureSummary();

    pruneVisitors(false);

    if (summaryCountNode) {
        summaryCountNode.textContent = activeVisitors.size.toString();
    }
};

const pruneVisitors = (shouldUpdate = true) => {
    const now = Date.now();

    for (const [id, info] of activeVisitors.entries()) {
        if (now - info.seenAt > visitorTtlMs) {
            activeVisitors.delete(id);
        }
    }

    if (shouldUpdate) {
        updateSummary();
    }
};

window.setInterval(() => pruneVisitors(true), 30_000);
