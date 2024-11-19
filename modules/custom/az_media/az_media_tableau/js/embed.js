const vizInstances = new Map(); // Store each viz instance with its associated div element

function getDashboardSize(paragraphElement) {
    const minHeight = 600,
        maxHeight = 1905,
        minWidth = 400;

    let height = maxHeight;
    const width = paragraphElement ? paragraphElement.offsetWidth : minWidth;

    if (height < minHeight) {
        height = minHeight;
    }
    if (height > maxHeight) {
        height = maxHeight;
    }
    return {
        'width': Math.max(width, minWidth),
        'height': height
    };
}

function adjustIFrame(vizElement) {
    const iframe = vizElement.querySelector('iframe');
    if (iframe) {
        iframe.setAttribute("scrolling", "yes");
        iframe.style.overflow = 'auto';
        iframe.style.overflowX = 'scroll';
        iframe.style.webkitOverflowScrolling = 'touch';
    }
}

document.addEventListener("DOMContentLoaded", () => {
    generateAllViz();
    document.querySelectorAll('div.viz').forEach(adjustIFrame);
});

function generateAllViz() {
    document.querySelectorAll('div.viz').forEach(visualization => {
        generateViz(visualization);
    });
}

function generateViz(visualization) {
    const url = visualization.getAttribute('data-url');

    // Dispose of the existing viz if one is already active for this element
    if (vizInstances.has(visualization)) {
        vizInstances.get(visualization).dispose();
    }

    const size = getDashboardSize(visualization);
    const options = {
        hideTabs: true,
        width: `${size.width - 5}px`,
        height: `${size.height}px`,
        onFirstInteractive: function () {
            const vizInstance = vizInstances.get(visualization);
            if (vizInstance) {
                const workbook = vizInstance.getWorkbook();
                const activeSheet = workbook.getActiveSheet();
                adjustIFrame(visualization);
            }
        }
    };

    const viz = new tableau.Viz(visualization, url, options);
    vizInstances.set(visualization, viz);
    visualization.style.border = "2px solid #dbdbdb";
}

function resizeAll() {
    document.querySelectorAll('div.viz').forEach(vizElement => {
        resizeViz(vizElement);
    });
}

function resizeViz(vizElement) {
    const size = getDashboardSize(vizElement);

    vizElement.style.width = `${size.width}px`;

    adjustIFrame(vizElement);

    const iframe = vizElement.querySelector('iframe');
    if (iframe) {
        iframe.style.width = `${size.width}px`;
    }
}

function changeHeight(vizElement, newChangedHeight) {
    const newHeight = newChangedHeight;
    const iframe = vizElement.querySelector('iframe');

    if (vizElement && iframe) {
        vizElement.style.height = `${newHeight + 24}px`;
        iframe.style.height = `${newHeight}px`;
        adjustIFrame(vizElement);
    }
}

function doOnOrientationChange() {
    vizInstances.forEach((viz, visualization) => {
        if (viz) {
            viz.dispose();
            generateViz(visualization);
        }
    });
}

window.addEventListener('orientationchange', doOnOrientationChange);
window.addEventListener('resize', resizeAll);
