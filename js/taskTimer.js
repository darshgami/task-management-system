class TaskTimer {
    constructor(taskId, triggerBtnSelector, displaySelector) {
        this.taskId = taskId;
        this.triggerBtn = document.querySelector(triggerBtnSelector);
        this.display = document.querySelector(displaySelector);
        this.logId = localStorage.getItem(`task_${taskId}_logId`) || null;
        this.startTime = localStorage.getItem(`task_${taskId}_start`) || null;
        this.timerInterval = null;
        this.isRunning = !!this.logId;

        if (this.triggerBtn) {
            this.triggerBtn.addEventListener('click', () => this.toggleTimer());
            this.updateButtonUI();
        }

        if (this.isRunning) {
            this.startTicker();
        }
    }

    async toggleTimer() {
        if (this.isRunning) {
            await this.stopTimer();
        } else {
            await this.startTimer();
        }
    }

    async startTimer() {
        try {
            const res = await fetch('api/timer_updates.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'start', task_id: this.taskId })
            });
            const data = await res.json();

            if (data.success) {
                this.logId = data.log_id;
                this.startTime = Date.now();
                localStorage.setItem(`task_${this.taskId}_logId`, this.logId);
                localStorage.setItem(`task_${this.taskId}_start`, this.startTime);
                this.isRunning = true;
                this.updateButtonUI();
                this.startTicker();
            }
        } catch (error) {
            console.error('Failed to start timer:', error);
        }
    }

    async stopTimer() {
        try {
            const res = await fetch('api/timer_updates.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'stop', task_id: this.taskId, log_id: this.logId })
            });
            const data = await res.json();

            if (data.success) {
                this.isRunning = false;
                this.logId = null;
                localStorage.removeItem(`task_${this.taskId}_logId`);
                localStorage.removeItem(`task_${this.taskId}_start`);
                clearInterval(this.timerInterval);
                this.updateButtonUI();

                // Show notification safely
                alert(`Task paused! Added ${data.added_seconds}s to total time.`);
            }
        } catch (error) {
            console.error('Failed to stop timer:', error);
        }
    }

    startTicker() {
        this.timerInterval = setInterval(() => {
            if (!this.display) return;
            const diff = Math.floor((Date.now() - this.startTime) / 1000);
            const hrs = String(Math.floor(diff / 3600)).padStart(2, '0');
            const mins = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
            const secs = String(diff % 60).padStart(2, '0');
            this.display.innerText = `${hrs}:${mins}:${secs}`;
        }, 1000);
    }

    updateButtonUI() {
        if (!this.triggerBtn) return;
        if (this.isRunning) {
            this.triggerBtn.innerText = 'Pause Working';
            this.triggerBtn.className = 'btn danger';
        } else {
            this.triggerBtn.innerText = 'Start Working';
            this.triggerBtn.className = 'btn success';
        }
    }
}
