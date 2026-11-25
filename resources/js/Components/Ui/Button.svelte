<script lang="ts">
    import type { Snippet } from 'svelte';
    import { buttonClass, type ButtonVariant } from '../../lib/ui';

    let {
        variant = 'primary',
        type = 'button',
        disabled = false,
        onclick,
        class: className = '',
        children,
    }: {
        variant?: ButtonVariant;
        type?: 'button' | 'submit' | 'reset';
        disabled?: boolean;
        onclick?: (event: MouseEvent) => void;
        class?: string;
        children: Snippet;
    } = $props();
</script>

<button {type} {disabled} {onclick} class={buttonClass(variant, className)}>
    {@render children()}
</button>
