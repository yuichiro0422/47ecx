function comingSoonLinks() {
    const comingSoonLinks = document.querySelectorAll('.coming_soon_link')
    if (0 < comingSoonLinks.length) {
        comingSoonLinks.forEach((link) => {
            link.addEventListener('click', (ev) => {
                ev.preventDefault()
                alert('Coming Soon...')
            })
        })
    }
}

document.addEventListener('DOMContentLoaded', () => {
    comingSoonLinks()
})
