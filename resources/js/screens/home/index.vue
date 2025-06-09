<template>
    <div class="container mt-5">
        <h1 class="mb-4">Telescope Home</h1>
        <div class="mb-4">
            <label for="date-picker" class="mr-2">Select Date:</label>
            <input id="date-picker" type="date" v-model="date" class="form-control d-inline-block w-auto" />
        </div>
        <div class="row">
            <div class="col-md-3 mb-4" v-for="stat in statCards" :key="stat.key">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <div class="display-4 mb-2">{{ formatNumber(stats[stat.key]) }}</div>
                        <div class="h5">{{ stat.label }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';

export default {
    name: 'HomeScreen',
    data() {
        return {
            date: this.today(),
            stats: {
                request: 0,
                jobs: 0,
                exception: 0,
                mail: 0,
                queries: 0,
            },
            statCards: [
                { key: 'request', label: 'Requests' },
                { key: 'jobs', label: 'Jobs' },
                { key: 'exception', label: 'Exceptions' },
                { key: 'mail', label: 'Mails' },
                { key: 'queries', label: 'Queries' },
            ],
        };
    },
    methods: {
        today() {
            const d = new Date();
            return d.toISOString().slice(0, 10);
        },
        fetchStats() {
            axios.get(window.Telescope.basePath + '/telescope-api/home-stats?date=' + this.date)
                .then(res => {
                    this.stats = res.data;
                })
                .catch(() => {
                    this.stats = {
                        request: 0,
                        jobs: 0,
                        exception: 0,
                        mail: 0,
                        queries: 0,
                    };
                });
        },
        formatNumber(value) {
            if (value === null || value === undefined) return 0;
            if (value < 1000) return value;
            if (value < 1000000) return (value / 1000).toFixed(1).replace(/\.0$/, '') + 'K';
            if (value < 1000000000) return (value / 1000000).toFixed(1).replace(/\.0$/, '') + 'M';
            return (value / 1000000000).toFixed(1).replace(/\.0$/, '') + 'B';
        },
    },
    mounted() {
        this.fetchStats();
        this.statsInterval = setInterval(this.fetchStats, 10000); // update every 10 seconds
    },
    beforeDestroy() {
        if (this.statsInterval) {
            clearInterval(this.statsInterval);
        }
    },
    watch: {
        date() {
            this.fetchStats();
        },
    },
};
</script>

<style scoped>
.card {
    border-radius: 1rem;
    border: none;
    background: #f8fafc;
}
.display-4 {
    font-size: 2.5rem;
    color: #4f8edc;
    font-weight: bold;
}
.h5 {
    color: #333;
}
</style> 