<template>
    <div class="container mt-5">
        <h1 class="mb-4">Telescope Home</h1>
        <div class="mb-4">
            <label for="date-picker" class="mr-2">Select Date:</label>
            <input id="date-picker" type="date" v-model="date" @change="fetchStats" class="form-control d-inline-block w-auto" />
        </div>
        <div class="row">
            <div class="col-md-3 mb-4" v-for="stat in statCards" :key="stat.key">
                <div class="card text-center shadow-sm">
                    <div class="card-body">
                        <div class="display-4 mb-2">{{ stats[stat.key] }}</div>
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
                requests: 0,
                jobs: 0,
                exceptions: 0,
                mails: 0,
                queries: 0,
            },
            statCards: [
                { key: 'requests', label: 'Requests' },
                { key: 'jobs', label: 'Jobs' },
                { key: 'exceptions', label: 'Exceptions' },
                { key: 'mails', label: 'Mails' },
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
                        requests: 0,
                        jobs: 0,
                        exceptions: 0,
                        mails: 0,
                        queries: 0,
                    };
                });
        },
    },
    mounted() {
        this.fetchStats();
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