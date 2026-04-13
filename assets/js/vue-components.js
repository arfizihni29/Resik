


const VueComponents = {

    AnimatedCounter: {
        props: {
            targetValue: {
                type: Number,
                required: true
            },
            duration: {
                type: Number,
                default: 2000
            },
            suffix: {
                type: String,
                default: ''
            }
        },
        data() {
            return {
                currentValue: 0,
                isVisible: false
            }
        },
        mounted() {
            this.observeElement();
        },
        methods: {
            observeElement() {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !this.isVisible) {
                            this.isVisible = true;
                            this.animateCounter();
                        }
                    });
                });
                observer.observe(this.$el);
            },
            animateCounter() {
                const start = 0;
                const end = this.targetValue;
                const duration = this.duration;
                const startTime = Date.now();
                
                const animate = () => {
                    const currentTime = Date.now();
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    

                    const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                    this.currentValue = Math.floor(start + (end - start) * easeOutQuart);
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        this.currentValue = end;
                    }
                };
                
                animate();
            }
        },
        template: `
            <h3 class="counter-value" :class="{ 'counting': isVisible }">
                {{ currentValue }}{{ suffix }}
            </h3>
        `
    },


    StatsCard: {
        props: {
            value: {
                type: Number,
                required: true
            },
            label: {
                type: String,
                required: true
            },
            icon: {
                type: String,
                required: true
            },
            variant: {
                type: String,
                default: 'primary'
            },
            duration: {
                type: Number,
                default: 2000
            }
        },
        data() {
            return {
                isHovered: false,
                isVisible: false,
                currentValue: 0,
                hasAnimated: false
            }
        },
        mounted() {
            this.observeElement();
        },
        methods: {
            observeElement() {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !this.hasAnimated) {
                            this.isVisible = true;
                            this.hasAnimated = true;
                            this.animateCounter();
                        }
                    });
                });
                observer.observe(this.$el);
            },
            animateCounter() {
                const start = 0;
                const end = this.value;
                const duration = this.duration;
                const startTime = Date.now();
                
                const animate = () => {
                    const currentTime = Date.now();
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    

                    const easeOutQuart = 1 - Math.pow(1 - progress, 4);
                    this.currentValue = Math.floor(start + (end - start) * easeOutQuart);
                    
                    if (progress < 1) {
                        requestAnimationFrame(animate);
                    } else {
                        this.currentValue = end;
                    }
                };
                
                animate();
            }
        },
        template: `
            <div 
                class="stat-card mobile-card" 
                :class="['stat-card-' + variant, { 'visible': isVisible, 'hovered': isHovered }]"
                @mouseenter="isHovered = true"
                @mouseleave="isHovered = false"
                @touchstart="isHovered = true"
                @touchend="isHovered = false"
            >
                <div class="stat-icon">
                    <i :class="icon"></i>
                </div>
                <h3 class="counter-value" :class="{ 'counting': isVisible }">
                    {{ currentValue }}
                </h3>
                <p class="stat-label">{{ label }}</p>
                <div class="stat-card-glow"></div>
            </div>
        `
    },


    MobileCard: {
        props: {
            title: {
                type: String,
                required: true
            },
            icon: {
                type: String,
                default: 'fas fa-info-circle'
            },
            variant: {
                type: String,
                default: 'default'
            },
            clickable: {
                type: Boolean,
                default: false
            }
        },
        data() {
            return {
                isPressed: false
            }
        },
        methods: {
            handleClick() {
                if (this.clickable) {
                    this.$emit('click');
                }
            }
        },
        template: `
            <div 
                class="mobile-card" 
                :class="[
                    'card-' + variant, 
                    { 'clickable': clickable, 'pressed': isPressed }
                ]"
                @click="handleClick"
                @touchstart="isPressed = true"
                @touchend="isPressed = false"
            >
                <div class="mobile-card-header">
                    <i :class="icon" class="mobile-card-icon"></i>
                    <h5 class="mobile-card-title">{{ title }}</h5>
                </div>
                <div class="mobile-card-body">
                    <slot></slot>
                </div>
            </div>
        `
    },


    BottomNav: {
        props: {
            items: {
                type: Array,
                required: true
            },
            activeItem: {
                type: String,
                required: true
            }
        },
        data() {
            return {
                isVisible: true,
                lastScrollY: 0
            }
        },
        mounted() {
            window.addEventListener('scroll', this.handleScroll);
        },
        beforeUnmount() {
            window.removeEventListener('scroll', this.handleScroll);
        },
        methods: {
            handleScroll() {
                const currentScrollY = window.scrollY;
                

                if (currentScrollY > this.lastScrollY && currentScrollY > 100) {
                    this.isVisible = false;
                } else {
                    this.isVisible = true;
                }
                
                this.lastScrollY = currentScrollY;
            },
            isActive(itemHref) {
                return this.activeItem === itemHref;
            }
        },
        template: `
            <div class="bottom-nav" :class="{ 'hidden': !isVisible }">
                <div class="bottom-nav-container">
                    <a 
                        v-for="item in items" 
                        :key="item.href"
                        :href="item.href"
                        class="bottom-nav-item"
                        :class="{ 'active': isActive(item.href) }"
                    >
                        <div class="bottom-nav-icon-wrapper">
                            <i :class="item.icon"></i>
                            <span v-if="item.badge" class="bottom-nav-badge">{{ item.badge }}</span>
                        </div>
                        <span class="bottom-nav-label">{{ item.label }}</span>
                    </a>
                </div>
            </div>
        `
    },


    SwipeCard: {
        props: {
            data: {
                type: Object,
                required: true
            }
        },
        data() {
            return {
                startX: 0,
                currentX: 0,
                isDragging: false,
                swipeDirection: null
            }
        },
        computed: {
            translateX() {
                if (!this.isDragging) return 0;
                return this.currentX - this.startX;
            }
        },
        methods: {
            handleTouchStart(e) {
                this.startX = e.touches[0].clientX;
                this.isDragging = true;
            },
            handleTouchMove(e) {
                if (!this.isDragging) return;
                this.currentX = e.touches[0].clientX;
            },
            handleTouchEnd() {
                if (!this.isDragging) return;
                
                const diff = this.currentX - this.startX;
                
                if (Math.abs(diff) > 100) {
                    if (diff > 0) {
                        this.$emit('swipe-right', this.data);
                    } else {
                        this.$emit('swipe-left', this.data);
                    }
                }
                
                this.isDragging = false;
                this.currentX = 0;
                this.startX = 0;
            }
        },
        template: `
            <div 
                class="swipe-card"
                :style="{ transform: 'translateX(' + translateX + 'px)' }"
                @touchstart="handleTouchStart"
                @touchmove="handleTouchMove"
                @touchend="handleTouchEnd"
            >
                <slot></slot>
            </div>
        `
    },


    PullToRefresh: {
        data() {
            return {
                startY: 0,
                currentY: 0,
                isPulling: false,
                isRefreshing: false,
                pullDistance: 0
            }
        },
        computed: {
            pullProgress() {
                const maxPull = 100;
                return Math.min(this.pullDistance / maxPull, 1);
            },
            shouldRefresh() {
                return this.pullDistance > 80;
            }
        },
        methods: {
            handleTouchStart(e) {
                if (window.scrollY === 0) {
                    this.startY = e.touches[0].clientY;
                    this.isPulling = true;
                }
            },
            handleTouchMove(e) {
                if (!this.isPulling || this.isRefreshing) return;
                
                this.currentY = e.touches[0].clientY;
                this.pullDistance = Math.max(0, this.currentY - this.startY);
                
                if (this.pullDistance > 0) {
                    e.preventDefault();
                }
            },
            handleTouchEnd() {
                if (!this.isPulling) return;
                
                if (this.shouldRefresh) {
                    this.refresh();
                }
                
                this.isPulling = false;
                this.pullDistance = 0;
            },
            async refresh() {
                this.isRefreshing = true;
                this.$emit('refresh');
                

                await new Promise(resolve => setTimeout(resolve, 1000));
                
                this.isRefreshing = false;
                this.pullDistance = 0;
            }
        },
        template: `
            <div 
                class="pull-to-refresh"
                @touchstart="handleTouchStart"
                @touchmove="handleTouchMove"
                @touchend="handleTouchEnd"
            >
                <div 
                    class="pull-indicator"
                    :class="{ 'active': isPulling, 'refreshing': isRefreshing }"
                    :style="{ transform: 'translateY(' + Math.min(pullDistance, 100) + 'px)' }"
                >
                    <i 
                        class="fas" 
                        :class="isRefreshing ? 'fa-spinner fa-spin' : 'fa-arrow-down'"
                    ></i>
                    <span>{{ isRefreshing ? 'Refreshing...' : shouldRefresh ? 'Release to refresh' : 'Pull to refresh' }}</span>
                </div>
                <slot></slot>
            </div>
        `
    },


    ToastNotification: {
        data() {
            return {
                toasts: []
            }
        },
        methods: {
            addToast({ type = 'info', title = '', message = '', duration = 3000 }) {
                const id = Date.now();
                this.toasts.push({ id, type, title, message });
                
                if (duration > 0) {
                    setTimeout(() => {
                        this.removeToast(id);
                    }, duration);
                }
            },
            removeToast(id) {
                const index = this.toasts.findIndex(t => t.id === id);
                if (index > -1) {
                    this.toasts.splice(index, 1);
                }
            }
        },
        template: `
            <div class="toast-container">
                <transition-group name="toast">
                    <div 
                        v-for="toast in toasts" 
                        :key="toast.id"
                        class="toast"
                        :class="'toast-' + toast.type"
                        @click="removeToast(toast.id)"
                    >
                        <div class="toast-icon">
                            <i class="fas" :class="{
                                'fa-check-circle': toast.type === 'success',
                                'fa-info-circle': toast.type === 'info',
                                'fa-exclamation-triangle': toast.type === 'warning',
                                'fa-times-circle': toast.type === 'error'
                            }"></i>
                        </div>
                        <div class="toast-content">
                            <strong v-if="toast.title">{{ toast.title }}</strong>
                            <p>{{ toast.message }}</p>
                        </div>
                        <button class="toast-close" @click.stop="removeToast(toast.id)">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </transition-group>
            </div>
        `
    },


    MobileAccordion: {
        props: {
            items: {
                type: Array,
                required: true
            }
        },
        data() {
            return {
                activeIndex: null
            }
        },
        methods: {
            toggleItem(index) {
                this.activeIndex = this.activeIndex === index ? null : index;
            }
        },
        template: `
            <div class="mobile-accordion">
                <div 
                    v-for="(item, index) in items" 
                    :key="index"
                    class="accordion-item"
                    :class="{ 'active': activeIndex === index }"
                >
                    <div class="accordion-header" @click="toggleItem(index)">
                        <span class="accordion-title">
                            <i v-if="item.icon" :class="item.icon"></i>
                            {{ item.title }}
                        </span>
                        <i class="fas fa-chevron-down accordion-arrow"></i>
                    </div>
                    <transition name="accordion-content">
                        <div v-if="activeIndex === index" class="accordion-body">
                            <div class="accordion-content">
                                {{ item.content }}
                            </div>
                        </div>
                    </transition>
                </div>
            </div>
        `
    },


    FloatingActionButton: {
        props: {
            icon: {
                type: String,
                default: 'fas fa-plus'
            },
            actions: {
                type: Array,
                default: () => []
            }
        },
        data() {
            return {
                isOpen: false
            }
        },
        methods: {
            toggleMenu() {
                this.isOpen = !this.isOpen;
            },
            handleAction(action) {
                this.$emit('action', action);
                this.isOpen = false;
            }
        },
        template: `
            <div class="fab-container" :class="{ 'open': isOpen }">
                <transition-group name="fab-action" tag="div" class="fab-actions">
                    <a 
                        v-for="(action, index) in actions" 
                        v-show="isOpen"
                        :key="action.id"
                        :href="action.href"
                        class="fab-action"
                        :style="{ transitionDelay: (index * 0.05) + 's' }"
                        :title="action.label"
                    >
                        <i :class="action.icon"></i>
                        <span class="fab-action-label">{{ action.label }}</span>
                    </a>
                </transition-group>
                <button class="fab-button" @click="toggleMenu">
                    <i :class="icon" class="fab-icon" :class="{ 'rotate': isOpen }"></i>
                </button>
                <div v-if="isOpen" class="fab-backdrop" @click="toggleMenu"></div>
            </div>
        `
    },


    SkeletonLoader: {
        props: {
            type: {
                type: String,
                default: 'card' // card, text, avatar, image
            },
            count: {
                type: Number,
                default: 1
            }
        },
        template: `
            <div class="skeleton-container">
                <div v-for="n in count" :key="n" class="skeleton" :class="'skeleton-' + type">
                    <div v-if="type === 'card'" class="skeleton-card">
                        <div class="skeleton-image"></div>
                        <div class="skeleton-content">
                            <div class="skeleton-line"></div>
                            <div class="skeleton-line short"></div>
                        </div>
                    </div>
                    <div v-else-if="type === 'text'" class="skeleton-text">
                        <div class="skeleton-line"></div>
                        <div class="skeleton-line short"></div>
                    </div>
                    <div v-else-if="type === 'avatar'" class="skeleton-avatar"></div>
                    <div v-else-if="type === 'image'" class="skeleton-image"></div>
                </div>
            </div>
        `
    },


    DataTable: {
        props: {
            data: {
                type: Array,
                required: true
            },
            columns: {
                type: Array,
                required: true
            }
        },
        data() {
            return {
                sortKey: '',
                sortOrder: 'asc'
            }
        },
        computed: {
            sortedData() {
                if (!this.sortKey) return this.data;
                
                return [...this.data].sort((a, b) => {
                    let aVal = a[this.sortKey];
                    let bVal = b[this.sortKey];
                    
                    if (typeof aVal === 'string') {
                        aVal = aVal.toLowerCase();
                        bVal = bVal.toLowerCase();
                    }
                    
                    if (this.sortOrder === 'asc') {
                        return aVal > bVal ? 1 : -1;
                    } else {
                        return aVal < bVal ? 1 : -1;
                    }
                });
            }
        },
        methods: {
            sort(key) {
                if (this.sortKey === key) {
                    this.sortOrder = this.sortOrder === 'asc' ? 'desc' : 'asc';
                } else {
                    this.sortKey = key;
                    this.sortOrder = 'asc';
                }
            }
        },
        template: `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th 
                                v-for="column in columns" 
                                :key="column.key"
                                @click="column.sortable ? sort(column.key) : null"
                                :style="{ cursor: column.sortable ? 'pointer' : 'default' }"
                            >
                                {{ column.label }}
                                <i v-if="column.sortable && sortKey === column.key" 
                                   class="fas" 
                                   :class="sortOrder === 'asc' ? 'fa-sort-up' : 'fa-sort-down'"
                                ></i>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in sortedData" :key="item.id">
                            <slot :item="item"></slot>
                        </tr>
                    </tbody>
                </table>
            </div>
        `
    },


    ImageGallery: {
        props: {
            images: {
                type: Array,
                required: true
            }
        },
        data() {
            return {
                currentIndex: 0,
                isFullscreen: false
            }
        },
        methods: {
            openFullscreen(index) {
                this.currentIndex = index;
                this.isFullscreen = true;
            },
            closeFullscreen() {
                this.isFullscreen = false;
            },
            nextImage() {
                this.currentIndex = (this.currentIndex + 1) % this.images.length;
            },
            prevImage() {
                this.currentIndex = (this.currentIndex - 1 + this.images.length) % this.images.length;
            }
        },
        template: `
            <div class="image-gallery">
                <div class="gallery-grid">
                    <div 
                        v-for="(image, index) in images" 
                        :key="index"
                        class="gallery-item"
                        @click="openFullscreen(index)"
                    >
                        <img :src="image.src" :alt="image.alt" class="gallery-image">
                        <div class="gallery-overlay">
                            <i class="fas fa-search-plus"></i>
                        </div>
                    </div>
                </div>
                
                <transition name="fullscreen">
                    <div v-if="isFullscreen" class="gallery-fullscreen">
                        <button class="fullscreen-close" @click="closeFullscreen">
                            <i class="fas fa-times"></i>
                        </button>
                        <button class="fullscreen-nav prev" @click="prevImage">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="fullscreen-nav next" @click="nextImage">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                        <img :src="images[currentIndex].src" :alt="images[currentIndex].alt" class="fullscreen-image">
                        <div class="fullscreen-counter">
                            {{ currentIndex + 1 }} / {{ images.length }}
                        </div>
                    </div>
                </transition>
            </div>
        `
    }
};


if (typeof window !== 'undefined') {
    window.VueComponents = VueComponents;
}
