class DebugPanel {
    constructor() {
        this.autorefresh = false;
        this.currentNavigationId = null;

        this.buttons = document.querySelectorAll('.debugNavItem');
        this.buttons.forEach((button) => {
            button.addEventListener('click', async (event) => {
                this.currentNavigationId = event.currentTarget.id;
                this.updateMenu();
                await this.fetchContent();
            });
        });

        this.autoRefreshInput = document.querySelector('#autorefreshInput');
        this.autoRefreshInput.addEventListener('change', (event) => {
            this.autorefresh = event.target.checked;
            if (event.target.checked) {
                setInterval(() => {
                    this.refreshContent();
                }, 1000);
            } else {
                clearInterval();
            }
        });

        this.adminContent = document.querySelector('.adminContent');
        this.debugContent = document.querySelector('.debugcontent');

        if (this.buttons.length) {
            this.buttons[0].click();
        }
    }

    async updateMenu() {
        this.buttons.forEach((button) => {
            button.classList.remove('isActive');
        });
        const button = Array.from(this.buttons).find((button) => button.id === this.currentNavigationId);
        if (button) {
            button.classList.add('isActive');
        }
    }

    async refreshContent() {
        if (this.autorefresh) {
            await this.fetchContent();
        }
    }

    async fetchContent() {
        return fetch(config.foldersPublic.api + '/serverInfo.php?content=' + this.currentNavigationId)
            .then((response) => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then((html) => {
                this.debugContent.innerHTML = '<pre class="break-all whitespace-pre-wrap">' + html + '</pre>';
                if (
                    ['nav-devlog', 'nav-remotebuzzerlog', 'nav-synctodrivelog'].indexOf(this.currentNavigationId) != -1
                ) {
                    this.adminContent.scrollTo(0, this.adminContent.scrollHeight);
                } else {
                    this.adminContent.scrollTo(0, 0);
                }
            })
            .catch((error) => {
                this.debugContent.innerHTML = error;
            });
    }
}

// eslint-disable-next-line no-unused-vars
const debugPanel = new DebugPanel();
