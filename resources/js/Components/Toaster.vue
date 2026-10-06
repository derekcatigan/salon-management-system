<!-- resources\js\Components\Toaster.vue -->
<script setup>
import { Toaster } from "vue-sonner";
</script>
<template>
    <Toaster
        richColors
        :closeButton="true"
        closeButtonPosition="bottom-left"
        position="top-right"
        :visibleToasts="5"
    />
</template>
