/** @type {import('tailwindcss').Config} */

tailwind.config = {
    theme: {
        extend: {
            colors: {
                ink:        '#16231C',
                inkSoft:    '#3C4A40',
                paper:      '#F6EEDD',
                paper2:     '#EDE2C6',
                paper3:     '#FBF7EC',
                copper:     '#C1712F',
                copperDeep: '#9C5822',
                teal:       '#2F6F6E',
                tealDeep:   '#204E4D',
                success:    '#4C7A50',
                danger:     '#B33F3F',
            },
            fontFamily: {
                display: ['Fraunces', 'serif'],
                sans:    ['Inter', 'sans-serif'],
                mono:    ['"IBM Plex Mono"', 'monospace'],
            },
        },
    },
};
