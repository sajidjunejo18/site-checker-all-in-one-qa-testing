(function ($) {
    $(document).ready(function () {
        console.log("WP SiteChecker Plugin JS Loaded!");
    });
})(jQuery);

// Script for Loader
document.addEventListener('DOMContentLoaded', function() {
    var loader = document.getElementById('qaLoaderOverlay');
    var content = document.getElementById('actualContent');
    
    if (loader) {
        loader.style.display = 'none';
    }
    if (content) {
        content.style.display = 'block';
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const urlInput = document.getElementById('urls') || document.getElementById('dummy-text');
    const urlTagsContainer = document.getElementById('url-tags-container');
    const urlsHiddenInput = document.getElementById('urls-hidden');
    
    
    const addUrlBtn = document.getElementById('add-url-btn');
    

    urlInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            addUrlTag();
        }
    });
    
    addUrlBtn.addEventListener('click', function(e) {
        e.preventDefault(); // prevent any default form action
        addUrlTag();
    });
    
    function addUrlTag() {
        const url = urlInput.value.trim();
        
        if (!url) return;
        
        // Basic URL validation
        try {
            new URL(url.startsWith('http') ? url : 'https://' + url);
        } catch (e) {
            alert('Please enter a valid URL');
            return;
        }

        
        // Create the tag element
        const tag = document.createElement('div');
        tag.className = 'url-tag';
        tag.innerHTML = `
            ${url}
            <span class="remove-btn" title="Remove">×</span>
        `;
        
        // Add remove functionality
        tag.querySelector('.remove-btn').addEventListener('click', function() {
            tag.remove();
            updateHiddenInput();
        });
        
        urlTagsContainer.appendChild(tag);
        urlInput.value = '';
        updateHiddenInput();
    }
    
    function updateHiddenInput() {
        const tags = Array.from(urlTagsContainer.querySelectorAll('.url-tag'));
        const urls = tags.map(tag => tag.textContent.replace('×', '').trim());
        urlsHiddenInput.value = urls.join(',');
    }
});

//Script for Broken Links
document.addEventListener("DOMContentLoaded", function () {
    const rows = document.querySelectorAll(".broken-link-row");
    const loadMoreBtn = document.getElementById("loadMoreBrokenLinks");
    const downloadBtn = document.getElementById('downloadBtn');
    let visibleCount = 10;
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener("click", function () {
            let shown = 0;
            for (let i = visibleCount; i < rows.length && shown < 10; i++) {
                rows[i].style.display = "table-row";
                shown++;
            }
            visibleCount += 10;
            if (visibleCount >= rows.length) {
                loadMoreBtn.style.display = "none";
            }
        });
    }
    if (downloadBtn) {
    downloadBtn.addEventListener('click', async () => {
        const reportData = JSON.parse(downloadBtn.getAttribute('data-report'));
        const type = downloadBtn.getAttribute('data-type');
        const parentUrl = downloadBtn.getAttribute('data-parent-url');

        // Create a temporary container for rendering the report
        const container = document.createElement('div');
        container.style.width = '1200px';
        container.style.padding = '40px';
        container.style.fontFamily = "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif";
        container.innerHTML = `
            <div style="background-color: #f9fafb; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                <h1 style="text-align: center; font-size: 28px; color: #1f2937;">Broken URL For Site</h1>
                <div style="margin-top: 20px; background-color: #f3f4f6; padding: 15px 20px; border-radius: 8px; font-size: 16px;">
                    <p><strong>Analyzed URL:</strong> <span style="color: #dc2626;">${parentUrl}</span></p>
                </div>
                <h2 style="margin-top: 30px; font-size: 20px; color: #374151;">🔗 Broken links <span style="font-weight: normal;">Total: ${reportData.length}</span></h2>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px;">
                    <thead>
                        <tr style="background-color: #e5e7eb; color: #111827;">
                            <th style="padding: 10px; border: 1px solid #d1d5db;">SN</th>
                            <th style="padding: 10px; border: 1px solid #d1d5db;">Broken URL</th>
                            <th style="padding: 10px; border: 1px solid #d1d5db;">Parent URL</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${reportData.map((link, i) => `
                            <tr>
                                <td style="padding: 10px; border: 1px solid #e5e7eb;">${i + 1}</td>
                                <td style="padding: 10px; border: 1px solid #e5e7eb; color: #dc2626;">${link.url}</td>
                                <td style="padding: 10px; border: 1px solid #e5e7eb;">${parentUrl}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
        

        // Hide from view but add to DOM so html2canvas can capture it
        container.style.position = 'absolute';
        container.style.left = '-9999px';
        document.body.appendChild(container);

        // Wait for DOM rendering
        await new Promise(resolve => setTimeout(resolve, 500));

        // Capture as canvas
        html2canvas(container, { scale: 2 }).then(canvas => {
            const imgData = canvas.toDataURL('image/png');
            const pdf = new jspdf.jsPDF('p', 'mm', 'a4');
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save(`broken-${type}-report-${new Date().toISOString().slice(0, 10)}.pdf`);

            document.body.removeChild(container); // Clean up
        });
    });
}
});

//Script for Dummy Text
document.addEventListener('DOMContentLoaded', function () {
    const loadMoreButtons = document.querySelectorAll('.load-more-btn');

    loadMoreButtons.forEach(button => {
        button.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const list = document.getElementById(targetId);
            const hiddenItems = list.querySelectorAll('li[style*="display: none"]');

            hiddenItems.forEach(item => {
                item.style.display = 'list-item';
            });

            this.remove(); // Remove Load More button after expanding
        });
    });
});

// Accessibility
document.addEventListener('DOMContentLoaded', function () {
    const addBtn = document.getElementById('addBtn');
    const premiumLink = '<a href="https://wpsitechecker.com/pricing" target="_blank" style="color:#fff; text-decoration:underline; cursor:pointer;">Unlock More with Premium</a>';

    // Track if add was already clicked
    let addClicked = false;

    if (addBtn) {
        addBtn.addEventListener('click', function () {
            if (addClicked) return;

            // Hide "Add" button
            addBtn.style.display = 'none';
            addClicked = true;

            // Create the premium message
            const premiumNotice = document.createElement('div');
            premiumNotice.className = 'premiumNotice';  // Main wrapper for the notice
            
            // Add the button with a class for easy styling
            premiumNotice.innerHTML = `
                <button type="button" class="premium-btn" id="new-btn" style="background:#f44336;color:#fff;border:none;padding:10px 16px;border-radius:5px;cursor:not-allowed;">
                    ${premiumLink}
                </button>
            `;

            // Insert the premiumNotice div after the "Add" button
            addBtn.parentNode.insertBefore(premiumNotice, addBtn.nextSibling);

            // If needed, you can update the class of the button dynamically
            const premiumButton = premiumNotice.querySelector('.premium-btn');
            
            // Dynamically change class here (e.g., adding another class)
            premiumButton.classList.add('new-premium-btn-class');
            // You can remove existing classes or toggle them
            premiumButton.classList.remove('premium-btn');
        });
    }
});

//Script for Downloading Dummy Text Report
document.addEventListener('DOMContentLoaded', function () {
    const downloadBtn = document.getElementById('download-report');
    const parentUrl = window.location.hostname;
    if (downloadBtn) {
        downloadBtn.addEventListener('click', async function () {
            const reportData = JSON.parse(this.getAttribute('data-report'));

            let html = `
            <div id="html-report-container" style="font-family: 'Poppins', sans-serif; background: #f9f9f9; padding: 40px; width: 1200px;">
                <h1 style="font-size: 32px; text-align: center; font-weight: 600; color: #2b2b2b;">Word Search Report</h1>
                    
                    <div style="margin-top: 10px; background-color: #f3f4f6; padding: 15px 20px; border-radius: 8px; font-size: 16px;">
                    <p><strong>Analyzed URL:</strong> <span style="color: #dc2626;">${parentUrl}</span></p>
                </div>
                    
                    <div style="margin-top: 5px;">
                    
                </div>`;

                reportData.forEach(entry => {
                    const isWorker = entry.keyword.toLowerCase().includes('worker');
                    const keywordTitle = entry.keyword || "Unknown Keyword";
                
                    html += `
                        <div style="margin-top: 30px;">
                            <h2 style="font-size: 20px; display: flex; align-items: center;">
                                🧾 <span style="margin-left: 10px;">${keywordTitle}</span>
                                <span style="margin-left: 15px; color: red; font-weight: 600;">(Used ${entry.usage_count} times)</span>
                            </h2>
                            ${isWorker ? `<strong style="color: #000; font-size: 16px;">Worker page</strong> <span style="color: red;">Time: ${entry.time || 'N/A'}</span>` : ''}
                            ${entry.matches.map(post => `
                                <div style="background: #fff; border-radius: 6px; padding: 15px; margin-bottom: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                                    <p style="margin: 0; color: #2b2b2b;">Lorem Ipsum is simply dummy text of the printing and typesetting industry.</p>
                                    <div style="margin-top: 5px; font-size: 13px; color: #777;">
                                        ${post.page ? `Page 0${post.page}` : 'Page'} - 
                                        <a href="${post.link}" style="color: #d14242; text-decoration: none;" target="_blank">${post.link}</a>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    `;
                });

            html += `</div></div>`;

            // Append to DOM for rendering
            const container = document.createElement('div');
            container.innerHTML = html;
            container.style.position = 'absolute';
            container.style.left = '-9999px';
            document.body.appendChild(container);

            const element = container.firstElementChild;

            const canvas = await html2canvas(element, {
                scale: 2,
                useCORS: true
            });

            const imgData = canvas.toDataURL('image/jpeg', 1.0);
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');

            const pageWidth = 210;
            const pageHeight = 297;
            const imgProps = pdf.getImageProperties(imgData);
            const imgWidth = pageWidth;
            const imgHeight = (imgProps.height * imgWidth) / imgProps.width;

            let heightLeft = imgHeight;
            let position = 0;

            pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft > 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }

            pdf.save('Word-Search-Report-' + new Date().toISOString().split('T')[0] + '.pdf');

            // Cleanup
            document.body.removeChild(container);
        });
    }

    // Utility to escape HTML
    function escapeHtml(text) {
        return text.replace(/[&<>"']/g, function (match) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return map[match];
        });
    }
});

//Script for PageSpeed
document.addEventListener('DOMContentLoaded', function () {
    // const addMoreBtn = document.getElementById('addMoreBtn');
    const premiumLink = '<a href="https://wpsitechecker.com/pricing" target="_blank" style="color:#fff; text-decoration:underline; cursor:pointer;">Unlock More with Premium</a>';
    const dropdownsContainer = document.getElementById('dropdownsContainer');
    const urlCountElement = document.getElementById('urlCount');
    const maxUrls = 3;
    let urlCount = 1;
    let currentSelectedPage = null; // Just the name/text of the page
    let currentSelectedPages = [];
    const resultsCache = {}; // To store results per page per strategy
    currentSelectedStrategy = '';
    
    // Function to handle Remove Page button clicks
    function handleDeleteClick(event) {
        const container = event.target.closest('.dropdown-container');
        if (container) {
            // Only remove if it's not the first dropdown
            if (dropdownsContainer.querySelectorAll('.dropdown-container').length > 1) {
                container.remove();
                urlCount--;
                urlCountElement.textContent = urlCount;
                
                // Show the "Add more" button if we're below max
                if (urlCount < maxUrls) {
                    addMoreBtn.style.display = 'block';
                }
                
                // Hide delete button on first dropdown if only one remains
                if (urlCount === 1) {
                    const firstDeleteBtn = dropdownsContainer.querySelector('.dropdown-container .delete-dropdown');
                    if (firstDeleteBtn) {
                        firstDeleteBtn.style.display = 'none';
                    }
                }
            }
        }
    }
    
    // Add event delegation for remove page buttons
    dropdownsContainer.addEventListener('click', function(event) {
        if (event.target.classList.contains('delete-dropdown')) {
            handleDeleteClick(event);
        }
    });
    
     // Function to show the "Free Trial Ended" button when the "Add more link" button is hidden
    function showPremiumMessage() {
        const premiumNotice = document.createElement('div');
        premiumNotice.className = 'premiumNotice'; // Wrapper for the notice
        premiumNotice.innerHTML = `
            <button type="button" class="premium-btn" style="background:#f44336;color:#fff;border:none;padding:10px 16px;border-radius:5px;cursor:not-allowed;">
                ${premiumLink}
            </button>
        `;

        // Insert the premium notice after the "Add more link" button
        const formContainer = document.querySelector('.pageSpeedSelect');
        formContainer.appendChild(premiumNotice);
    }

    const addMoreBtn = document.getElementById('addMoreBtn');
    if (addMoreBtn) {
    addMoreBtn.addEventListener('click', function () {
        // alert(maxUrls);
        if (urlCount >= maxUrls) return; // Prevent adding more than max
    
        const template = document.getElementById('dropdownTemplate');
        const clone = template.content.cloneNode(true);
        dropdownsContainer.appendChild(clone);
        urlCount++;
        urlCountElement.textContent = urlCount;
        
        // Show delete button on first dropdown when adding second dropdown
        if (urlCount === 2) {
            const firstDeleteBtn = dropdownsContainer.querySelector('.dropdown-container .delete-dropdown');
            if (firstDeleteBtn) {
                firstDeleteBtn.style.display = 'block';
            }
        }
        
        if (urlCount >= maxUrls) {
            addMoreBtn.style.display = 'none';
            showPremiumMessage();
        }
    });
    }
    const analyzeBtn = document.getElementById('analyzeBtn');
    const analysisButtonsContainer = document.getElementById('analysisButtonsContainer');
    const pageButtonsContainer = document.getElementById('pageButtons');

    analyzeBtn.addEventListener('click', function () {
        pageButtonsContainer.innerHTML = '';
        const selectElements = dropdownsContainer.querySelectorAll('select');
        const selectedPages = [];

        selectElements.forEach(select => {
            const selectedOption = select.options[select.selectedIndex];
            const selectedValue = select.value;
            if (selectedValue && selectedValue !== '') {
                selectedPages.push(selectedOption.textContent);
            }
        });


        const uniqueSelectedPages = [...new Set(selectedPages)];

        uniqueSelectedPages.forEach(page => {
            const pageButton = document.createElement('button');
            const trimmed = page.trim();

            // Apply trim ONLY if more than 5 pages are selected
            let buttonLabel = uniqueSelectedPages.length > 6
                ? (trimmed.length > 5 ? trimmed.slice(0, 5) + '...' : trimmed)
                : trimmed;

            pageButton.textContent = buttonLabel;
            pageButton.title = trimmed; // Full name on hover

            pageButton.addEventListener('click', function (e) {
                e.preventDefault();
                currentSelectedPage = trimmed; // store full page name

                if (currentSelectedPage) {
                    document.getElementById('mobileBtn').disabled = false;
                    document.getElementById('desktopBtn').disabled = false;
                }

                // Reset highlight
                document.querySelectorAll('#pageButtons button').forEach(btn => {
                    btn.style.backgroundColor = '';
                    btn.style.color = '';
                });

                this.style.backgroundColor = 'var(--base-color)';
                this.style.color = '#fff';

                // Clear device selection
                document.getElementById('mobileBtn').classList.remove('active');
                document.getElementById('desktopBtn').classList.remove('active');

                // Clear Core Web Vital buttons
                document.querySelectorAll('.vital-btn').forEach(btn => btn.classList.remove('active'));

                // Clear old data
                document.getElementById('coreVitalsButtons').style.display = 'none';
                document.getElementById('coreVitalsData').innerHTML = '';
                document.getElementById('downloadReportBtn').style.display = 'none';

                const resultsContainer = document.getElementById('resultsContainer');
                if (resultsContainer) resultsContainer.innerHTML = '';
            });

            pageButtonsContainer.appendChild(pageButton);
        });
        
        // Add lastBtn class if only one button exists
        const pageButtons = document.querySelectorAll('#pageButtons button');
        if (pageButtons.length === 1) {
            pageButtons[0].classList.add('lastBtn');
        }

        currentSelectedPages = [...new Set(selectedPages)];
        
        if (uniqueSelectedPages.length > 0) {
            analysisButtonsContainer.style.display = 'block';
            document.getElementById('deviceButtons').style.display = 'flex';
            
        } else {
            analysisButtonsContainer.style.display = 'none';
            document.getElementById('deviceButtons').style.display = 'none';   
        }
        
    });

    document.addEventListener('click', function (e) {
        if (!e.target.closest('#pageButtons button')) {
            document.body.style.backgroundColor = '';
        }
    });    
    
    document.getElementById('mobileBtn').addEventListener('click', function () {
        if (!currentSelectedPage) {
            const mobileBtn = document.getElementById('mobileBtn');
            const desktopBtn = document.getElementById('desktopBtn');
    
            mobileBtn.disabled = true;
            desktopBtn.disabled = true;
            mobileBtn.classList.remove('active');
            desktopBtn.classList.remove('active');
    
            // ✅ Reset styles explicitly
            mobileBtn.style.backgroundColor = '';
            mobileBtn.style.color = '';
            desktopBtn.style.backgroundColor = '';
            desktopBtn.style.color = '';
    
            document.body.style.backgroundColor = '';
            return;
        }

        currentSelectedStrategy = 'mobile';
        callPageSpeedApi('mobile');
        document.getElementById('downloadReportBtn').style.display = 'none';

        // Add 'active' to this button and remove from the other
        this.classList.add('active');
        document.getElementById('desktopBtn').classList.remove('active');

        //Clear Core Web vitals active 
        document.querySelectorAll('.vital-btn').forEach(btn => {
            btn.classList.remove('active');
        });

    });

    document.getElementById('desktopBtn').addEventListener('click', function () {
        if (!currentSelectedPage) {
            const mobileBtn = document.getElementById('mobileBtn');
            const desktopBtn = document.getElementById('desktopBtn');
    
            mobileBtn.disabled = true;
            desktopBtn.disabled = true;
            mobileBtn.classList.remove('active');
            desktopBtn.classList.remove('active');
    
            // ✅ Reset styles explicitly
            mobileBtn.style.backgroundColor = '';
            mobileBtn.style.color = '';
            desktopBtn.style.backgroundColor = '';
            desktopBtn.style.color = '';
    
            document.body.style.backgroundColor = '';
            return;
        }
        currentSelectedStrategy = 'desktop';
        callPageSpeedApi('desktop');
        
        //Hide Download Report button 
        document.getElementById('downloadReportBtn').style.display = 'none';
            
        // Add 'active' to this button and remove from the other
        this.classList.add('active');
        document.getElementById('mobileBtn').classList.remove('active');
    
        //Clear Core Web vitals active 
        document.querySelectorAll('.vital-btn').forEach(btn => {
            btn.classList.remove('active');
        });
    });   

    function getUrlForSelectedPage(pageName) {
        const selectElements = dropdownsContainer.querySelectorAll('select');
        for (const select of selectElements) {
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption.textContent.trim() === pageName.trim()) {
                return isValidUrl(select.value)
                    ? select.value
                    : selectedOption.getAttribute('data-url') || constructUrlFromPageName(pageName);
            }
        }
        return null;
    }

    function isValidUrl(url) {
        try {
            new URL(url);
            return true;
        } catch (_) {
            return false;
        }
    }

    function constructUrlFromPageName(pageName) {
        return `https://${pageName.toLowerCase().replace(/\s+/g, '')}.com`;
    }

    async function callPageSpeedApi(strategy) 
    {
        if (!currentSelectedPage) return;

        const pageUrl = getUrlForSelectedPage(currentSelectedPage);
        if (!pageUrl) {
            alert('Could not find URL for selected page');
            return;
        }

        // Clear UI
        document.getElementById('coreVitalsButtons').style.display = 'none';
        document.getElementById('coreVitalsData').innerHTML = '';

        // Cache check
        if (resultsCache[currentSelectedPage]?.[strategy]) {
            displayPageSpeedResults(resultsCache[currentSelectedPage][strategy], strategy, pageUrl, true);
            const coreVitals = resultsCache[currentSelectedPage][strategy].coreVitals;
            if (coreVitals) {
                document.getElementById('coreVitalsButtons').style.display = 'flex';
                registerCoreVitalsButtonListeners();
            }
            return;
        }

        const resultsContainer = document.getElementById('resultsContainer');
        resultsContainer.innerHTML = '<div id="loading"><p>Fetching results, please wait...</p><div class="moving-line"></div></div>';

        const categories = ["performance", "accessibility", "best-practices", "seo"];
        const apiKey = 'AIzaSyBt2uZkQNqjSCETycLiACwA849GUzr7yFg';

        const safePageUrl = pageUrl.includes('localhost') || !pageUrl.startsWith('http')
            ? 'https://www.linkedin.com'
            : pageUrl;

        // --- Helper to fetch with retry ---
        async function fetchWithRetry(category, retries = 1) {
            const apiUrl = `https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=${encodeURIComponent(safePageUrl)}&strategy=${strategy}&category=${category}&key=${apiKey}`;
            for (let attempt = 0; attempt <= retries; attempt++) {
                try {
                    const response = await fetch(apiUrl, { cache: "no-store" });
                    const data = await response.json();
                    if (data.error) throw new Error(data.error.message);
                    return { category, data };
                } catch (err) {
                    console.warn(`Attempt ${attempt + 1} for ${category} failed:`, err.message);
                    if (attempt === retries) return { category, error: err.message };
                    await new Promise(res => setTimeout(res, 800)); // small backoff
                }
            }
        }

        // --- Run two at a time (faster but safe) ---
        const batchSize = 2;
        const results = [];
        for (let i = 0; i < categories.length; i += batchSize) {
            const batch = categories.slice(i, i + batchSize);
            const batchResults = await Promise.all(batch.map(cat => fetchWithRetry(cat, 1)));
            results.push(...batchResults);
            // Small delay between batches
            await new Promise(res => setTimeout(res, 500));
        }

        document.getElementById('loading')?.remove();

        // Cache
        if (!resultsCache[currentSelectedPage]) resultsCache[currentSelectedPage] = {};
        resultsCache[currentSelectedPage][strategy] = results;

        // Filter out failed
        const validResults = results.filter(r => r.data);
        if (!validResults.length) {
            resultsContainer.innerHTML = '<p style="color:red;">All PageSpeed requests failed. Try again later.</p>';
            return;
        }

        displayPageSpeedResults(validResults, strategy, pageUrl, false);

        // Extract Core Web Vitals
        const perf = validResults.find(r => r.category === 'performance');
        if (perf?.data?.lighthouseResult?.audits) {
            const audits = perf.data.lighthouseResult.audits;
            const metrics = {
                FCP: audits['first-contentful-paint']?.displayValue || 'N/A',
                LCP: audits['largest-contentful-paint']?.displayValue || 'N/A',
                TBT: audits['total-blocking-time']?.displayValue || 'N/A',
                CLS: audits['cumulative-layout-shift']?.displayValue || 'N/A'
            };
            resultsCache[currentSelectedPage][strategy].coreVitals = metrics;
            document.getElementById('coreVitalsButtons').style.display = 'flex';
            registerCoreVitalsButtonListeners();
        }
    }        

    function displayPageSpeedResults(results, strategy, pageUrl, fromCache) {
        const container = document.getElementById('resultsContainer');
        container.innerHTML = ``;
    
        const ringWrapper = document.createElement('div');
        ringWrapper.style.display = 'flex';
        ringWrapper.style.flexWrap = 'wrap';
        ringWrapper.style.gap = '30px';
    
        results.forEach(result => {
            const category = result.category;
            let score = 'N/A';
            if (!result.error && result.data.lighthouseResult.categories[category]) {
                const rawScore = result.data.lighthouseResult.categories[category].score;
                score = rawScore !== null ? Math.round(rawScore * 100) : 'N/A';
            }
    
            let color = '#cccccc';
            let degrees = 0;
            let display = 'N/A';
    
            if (score !== 'N/A') {
                const percentage = Math.min(score, 100);
                degrees = (percentage / 100) * 360;
                display = `${percentage}%`;
    
                if (percentage >= 90) {
                    color = '#2ecc71';
                    textColor = '#E5FAEF';
                } else if (percentage >= 50) {
                    color = '#f39c12';
                } else {
                    color = '#e74c3c';
                }
            }
    
            const ring = document.createElement('div');
            ring.className = 'score-container';
            ring.innerHTML = `
                <div 
                    class="score-ring" 
                    style="
                        background:
                            linear-gradient(${color}, ${color}) content-box,
                            conic-gradient(${color} 0deg ${degrees}deg, #e3e3e3 ${degrees}deg 360deg);
                    "
                >
                    <div class="score-text" style="color: ${color}">${display}</div>
                </div>
                <div class="score-label">${category.charAt(0).toUpperCase() + category.slice(1)}</div>
            `;
    
            ringWrapper.appendChild(ring);
        });
    
        container.appendChild(ringWrapper);
    }
    
    function registerCoreVitalsButtonListeners() {
        document.querySelectorAll('.vital-btn').forEach(btn => {
            btn.addEventListener('click', function () {

                 // Remove 'active' class from all buttons
                document.querySelectorAll('.vital-btn').forEach(b => b.classList.remove('active'));

                // Add 'active' class to the clicked button
                this.classList.add('active');

                const vital = this.getAttribute('data-vital');
                const cached = resultsCache[currentSelectedPage]?.[currentSelectedStrategy];
                
                if (vital === 'all') {
                    showAllDiagnosticData(cached, "All");
                    document.getElementById('downloadReportBtn').style.display = 'block';

                    // Store the latest report content for download
                    window.currentReportContent = generateReportHTML(currentSelectedPage, currentSelectedStrategy);
                } else {
                    const value = cached?.coreVitals?.[vital];
                    const coreVitalsContainer = document.getElementById('coreVitalsData');
        
                    if (value) {
                        coreVitalsContainer.innerHTML = `
                            <div style="margin-top: 10px;">
                                <h4>${vital}</h4>
                                <p style="font-size: 18px;"><strong>${value}</strong></p>
                                <div id="diagnostic-${vital}"></div> <!-- placeholder for more -->
                            </div>
                        `;
                    
                        // Then target the placeholder div
                        const diagnosticDiv = document.getElementById(`diagnostic-${vital}`);
                        showAllDiagnosticData(cached, vital);
                    } else {
                        coreVitalsContainer.innerHTML = `<div style="color:red;">${vital} value not available.</div>`;
                    }
                }
            });
        });
    }

    function showAllDiagnosticData(cachedData, category) {
        const element = document.getElementById('resultsContainer');

        if (!cachedData || !cachedData.coreVitals) {
            document.getElementById('coreVitalsData').innerHTML = '<div style="color:red;">Diagnostic data not available.</div>';
            return;
        }
        
        const performanceData = cachedData.find(r => r.category === 'performance' && !r.error);
        if (!performanceData) return;

        const audits = performanceData.data.lighthouseResult.audits;
        const diagnosticData = {
            renderBlockingResources: audits['render-blocking-resources'] || [],
            renderBlockingDescription: audits['render-blocking-resources']?.description || '',
            thirdPartySummary: audits['third-party-summary'] || [],
            domSize: audits['dom-size'] || '',
            unusedCssRules: audits['unused-css-rules'] || [],
            unusedJavascript: audits['unused-javascript'] || [],
            largestContentfulPaint: audits['largest-contentful-paint-element'] || {},
            mainThreadWork: audits['mainthread-work-breakdown'] || [],
            diagnostics: audits['diagnostics']?.details?.items || [],
            responsiveImages: audits['uses-responsive-images']?.details?.items || [],
            responsiveImagesDisplay: audits['uses-responsive-images']?.displayValue || '',
            userTextImages: audits['uses-text-compression']?.details?.items || [],
            longTasks: audits['long-tasks'] || [],
            minifyCss: audits['unminified-css'] || [],
            minifyJavascript: audits['unminified-javascript'] || [],
            usesOptimizedImages: audits['uses-optimized-images'] || [],
            serverResponseTime: audits['server-response-time'] || [],
            redirects: audits['redirects'] || [],
            duplicatedJavascript: audits['duplicated-javascript'] || [],
            domSize: audits['dom-size'] || [],
            criticalRequestChains: audits['critical-request-chains'] || []
        };
        
        let reportContent = '<div style="max-width: 800px; margin: 0 auto;">';
    
        if(category === "All"){
        // Core Web Vitals Summary
        reportContent += '<div class="section" style="margin-bottom: 20px;">';
        reportContent += '<h3 style="margin-bottom: 15px;">Core Web Vitals</h3>';
        reportContent += '<div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">';
        for (const [metric, value] of Object.entries(cachedData.coreVitals)) {
            reportContent += `
                <div style="background: #f9f9f9; padding: 10px; border-radius: 6px;">
                    <strong>${metric}:</strong> ${value}
                </div>
            `;
        }
        reportContent += '</div></div>';
        }

        // Diagnostics Section
        if (diagnosticData.diagnostics.length > 0) {
            reportContent += '<div class="section" style="margin-top:20px;">';
            reportContent += '<h3 style="margin-bottom:15px;border-bottom:1px solid #eee;padding-bottom:8px;">DIAGNOSTICS</h3>';
            reportContent += '<ul style="list-style-type:none;padding-left:0;">';
            
            diagnosticData.diagnostics.forEach(item => {
                // Extract the main diagnostic item
                const diagnosticText = item.diagnostic || item.label || '';
                const diagnosticValue = item.value || '';
                
                reportContent += `
                    <li style="margin-bottom:12px;padding-left:20px;position:relative;">
                        <div style="font-weight:bold;margin-bottom:4px;">
                            <span style="position:absolute;left:0;">•</span> ${diagnosticText}
                            ${diagnosticValue ? `<span style="font-weight:normal;color:#666;"> — ${diagnosticValue}</span>` : ''}
                        </div>
                `;
                
                // Check for sub-items
                if (item.subItems && item.subItems.items && item.subItems.items.length > 0) {
                    reportContent += '<ul style="list-style-type:none;padding-left:20px;margin-top:6px;margin-bottom:8px;">';
                    item.subItems.items.forEach(subItem => {
                        const subText = subItem.label || subItem.diagnostic || '';
                        const subValue = subItem.value || '';
                        
                        reportContent += `
                            <li style="margin-bottom:4px;position:relative;">
                                <span style="position:absolute;left:0;">◦</span> ${subText}
                                ${subValue ? `<span style="color:#666;"> — ${subValue}</span>` : ''}
                            </li>
                        `;
                    });
                    reportContent += '</ul>';
                }
                
                reportContent += '</li>';
            });
            
            reportContent += '</ul>';
            reportContent += '<p style="color:#666;font-size:0.9em;margin-top:10px;">';
            reportContent += 'More information about the performance of your application. ';
            reportContent += 'These numbers don\'t directly affect the Performance score.';
            reportContent += '</p>';
            reportContent += '</div>';
        }
        
        if(category === "All" || category === "LCP"){
        // LCP Elements Section
        if (diagnosticData.largestContentfulPaint?.details?.items?.length > 0) {
            const lcpAudit = diagnosticData.largestContentfulPaint;

            reportContent += `
                <div class="section" style="margin-top: 20px; font-family: Arial, sans-serif;">
                    <div style="padding: 12px 16px; background-color: #f0f7ff; border-left: 4px solid #2196F3; margin-bottom: 20px;">
                        <span style="color: #0d47a1; font-weight: bold;">
                            &#128187; ${lcpAudit.title || 'Largest Contentful Paint'} — ${lcpAudit.displayValue || ''}
                        </span>
                        <div style="margin-top: 6px; color: #555; font-size: 14px;">
                            ${lcpAudit.description || ''}
                        </div>
                    </div>
            `;

            // Filter and render tables
            const lcpTables = lcpAudit.details.items.filter(item => item.type === 'table');

            // Elements Table
            const elementsTable = lcpTables.find(table =>
                table.headings?.some(heading => heading.key === 'node')
            );

            if (elementsTable?.items?.length > 0) {
                reportContent += `
                    <table style="width:100%; border-collapse:collapse; border:1px solid #ddd; margin-bottom:20px;">
                        <thead>
                            <tr style="background:#eee;">
                                <th style="padding:8px;border:1px solid #ccc;">Element</th>
                                <th style="padding:8px;border:1px solid #ccc;">Selector</th>
                                <th style="padding:8px;border:1px solid #ccc;">Size</th>
                                <th style="padding:8px;border:1px solid #ccc;">Node Label</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                elementsTable.items.forEach(item => {
                    const node = item.node || {};
                    reportContent += `
                        <tr>
                            <td style="padding:8px;border:1px solid #ccc;font-family:monospace;">${node.snippet || 'N/A'}</td>
                            <td style="padding:8px;border:1px solid #ccc;">${node.selector || 'N/A'}</td>
                            <td style="padding:8px;border:1px solid #ccc;">${node.boundingRect?.width || 0}px × ${node.boundingRect?.height || 0}px</td>
                            <td style="padding:8px;border:1px solid #ccc;">${node.nodeLabel || 'N/A'}</td>
                        </tr>
                    `;
                });

                reportContent += '</tbody></table>';
            }

            // Breakdown Table
            const breakdownTable = lcpTables.find(table =>
                table.headings?.some(heading => heading.key === 'phase')
            );

            if (breakdownTable?.items?.length > 0) {
                reportContent += '<h4 style="margin-bottom:10px;">LCP Phase Breakdown</h4>';
                reportContent += `
                    <table style="width:100%; border-collapse:collapse; border:1px solid #ddd;">
                        <thead>
                            <tr style="background:#eee;">
                                <th style="padding:8px;border:1px solid #ccc;">Phase</th>
                                <th style="padding:8px;border:1px solid #ccc;">% of LCP</th>
                                <th style="padding:8px;border:1px solid #ccc;">Timing (ms)</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                breakdownTable.items.forEach(item => {
                    reportContent += `
                        <tr>
                            <td style="padding:8px;border:1px solid #ccc;">${item.phase || 'N/A'}</td>
                            <td style="padding:8px;border:1px solid #ccc;">${item.percent || 'N/A'}</td>
                            <td style="padding:8px;border:1px solid #ccc;">${Math.round(item.timing) || 0}</td>
                        </tr>
                    `;
                });

                reportContent += '</tbody></table>';
            }

            reportContent += '</div>'; // Close main section
        } else {
            reportContent += '<p>No LCP data available.</p>';
        }
        }
        

        if (diagnosticData.responsiveImages && diagnosticData.responsiveImages.length > 0) {
            const responsiveAudit = diagnosticData.responsiveImages;
        
            reportContent += `
                <div style="margin-top: 30px; border: 1px solid #ccc; border-radius: 4px; font-family: Arial, sans-serif;">
                    <div style="padding: 12px 16px; background-color: #fef2f2; border-bottom: 1px solid #ccc;">
                        <span style="color: #c00; font-weight: bold;">
                            &#9888; ${responsiveAudit?.title || 'Responsive Images Issue'} — ${diagnosticData.responsiveImagesDisplay || ''}
                        </span>
                        <span style="float: right; color: #c00; font-weight: bold;">${responsiveAudit?.displayValue || ''}</span>
                        <div style="margin-top: 6px; color: #555; font-size: 14px;">
                            ${responsiveAudit?.description || ''}
                        </div>
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                        <thead>
                            <tr style="background-color: #f9f9f9; text-align: left;">
                                <th style="padding: 10px; border-bottom: 1px solid #ddd;">URL</th>
                                <th style="padding: 10px; border-bottom: 1px solid #ddd;">Resource Size</th>
                                <th style="padding: 10px; border-bottom: 1px solid #ddd;">Est Savings</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${diagnosticData.responsiveImages.map(item => `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px; word-break: break-all;">
                                        <a href="${item.url}" target="_blank" style="color: #1a73e8;">${item.url}</a>
                                    </td>
                                    <td style="padding: 10px;">${(item.totalBytes / 1024).toFixed(1)} KiB</td>
                                    <td style="padding: 10px; color: #c00;">${(item.wastedBytes / 1024).toFixed(1)} KiB</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        if(category === "All" || category === "LCP" || category === "FCP"){
        //Minify Css
        if (
            diagnosticData.minifyCss &&
            diagnosticData.minifyCss.details &&
            diagnosticData.minifyCss.details.items &&
            diagnosticData.minifyCss.details.items.length > 0
        ) {
            const items = diagnosticData.minifyCss.details.items;
        
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3 style="color: #d9534f;">
                        ${diagnosticData.minifyCss.title || 'Unminified CSS'}
                        ${diagnosticData.minifyCss.displayValue ? ' — ' + diagnosticData.minifyCss.displayValue : ''}
                    </h3>
                    <div style="color: #555; font-size: 14px;">
                        ${diagnosticData.minifyCss.description || ''}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">URL</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Transfer Size</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Est Savings</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => `
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                        ${item.url ? `<a href="${item.url}" target="_blank" style="color: #1a73e8;">${item.url}</a>` : 'N/A'}
                                    </td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${(item.totalBytes / 1024).toFixed(2)} KB</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${(item.wastedBytes / 1024).toFixed(2)} KB</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }        

        //Minify Javascript
        if (
            diagnosticData.minifyJavascript &&
            diagnosticData.minifyJavascript.details &&
            diagnosticData.minifyJavascript.details.items &&
            diagnosticData.minifyJavascript.details.items.length > 0
        ) {
            const items = diagnosticData.minifyJavascript.details.items;
        
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3 style="color: #d9534f;">
                        ${diagnosticData.minifyJavascript.title || 'Unminified JavaScript'}
                        ${diagnosticData.minifyJavascript.displayValue ? ' — ' + diagnosticData.minifyJavascript.displayValue : ''}
                    </h3>
                    <div style="color: #555; font-size: 14px;">
                        ${diagnosticData.minifyJavascript.description || ''}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">URL</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Transfer Size</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Est Savings</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => `
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                        ${item.url ? `<a href="${item.url}" target="_blank" style="color: #1a73e8;">${item.url}</a>` : 'N/A'}
                                    </td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${(item.totalBytes / 1024).toFixed(2)} KB</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${(item.wastedBytes / 1024).toFixed(2)} KB</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }        
    
        
        //Server Response Time
        if (diagnosticData.serverResponseTime && diagnosticData.serverResponseTime.details && diagnosticData.serverResponseTime.details.items && diagnosticData.serverResponseTime.details.items.length > 0
        ) {
            const items = diagnosticData.serverResponseTime.details.items;
        
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3 style="color: #d9534f;">
                        ${diagnosticData.serverResponseTime.title || 'Server Response Time'}
                        ${diagnosticData.serverResponseTime.displayValue ? ' — ' + diagnosticData.serverResponseTime.displayValue : ''}
                    </h3>
                    <div style="color: #555; font-size: 14px;">
                        ${diagnosticData.serverResponseTime.description || ''}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">URL</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Time Spent (ms)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => `
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                        ${item.url ? `<a href="${item.url}" target="_blank" style="color: #1a73e8;">${item.url}</a>` : 'N/A'}
                                    </td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${item.responseTime.toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        //Redirect
        if (
            diagnosticData.redirects &&
            diagnosticData.redirects.details &&
            diagnosticData.redirects.details.items &&
            diagnosticData.redirects.details.items.length > 0
        ) {
            const items = diagnosticData.redirects.details.items;
        
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3 style="color: #d9534f;">
                        ${diagnosticData.redirects.title || 'Redirects'}
                        ${diagnosticData.redirects.displayValue ? ' — ' + diagnosticData.redirects.displayValue : ''}
                    </h3>
                    <div style="color: #555; font-size: 14px;">
                        ${diagnosticData.redirects.description || ''}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">URL</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Time Spent (ms)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => `
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                        ${item.url ? `<a href="${item.url}" target="_blank" style="color: #1a73e8;">${item.url}</a>` : 'N/A'}
                                    </td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${item.wastedMs.toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        //Duplicate JavaScript
        if (
            diagnosticData.duplicatedJavascript &&
            diagnosticData.duplicatedJavascript.details &&
            diagnosticData.duplicatedJavascript.details.items &&
            diagnosticData.duplicatedJavascript.details.items.length > 0
        ) {
            const items = diagnosticData.duplicatedJavascript.details.items;
        
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3 style="color: #d9534f;">
                        ${diagnosticData.duplicatedJavascript.title || 'Duplicated JavaScript'}
                        ${diagnosticData.duplicatedJavascript.displayValue ? ' — ' + diagnosticData.duplicatedJavascript.displayValue : ''}
                    </h3>
                    <div style="color: #555; font-size: 14px;">
                        ${diagnosticData.duplicatedJavascript.description || ''}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Source</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Duplicated Bytes</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => `
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                        <code style="color: #c7254e; background-color: #f9f2f4; padding: 2px 4px;">${item.source || 'N/A'}</code>
                                    </td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${(item.wastedBytes || 0).toLocaleString()} bytes</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
    }
        //Dome Size
        if (
            diagnosticData.domSize &&
            diagnosticData.domSize.details &&
            diagnosticData.domSize.details.items &&
            diagnosticData.domSize.details.items.length > 0
        ) {
            const items = diagnosticData.domSize.details.items;
        
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3 style="color: #d9534f;">
                        ${diagnosticData.domSize.title || 'DOM Size'}
                        ${diagnosticData.domSize.displayValue ? ' — ' + diagnosticData.domSize.displayValue : ''}
                    </h3>
                    <div style="color: #555; font-size: 14px;">
                        ${diagnosticData.domSize.description || ''}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Statistic</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Element</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => `
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${item.statistic || 'N/A'}</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${item.node?.snippet || 'N/A'}</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${item.value || 0}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }        

        //Critical Request Chains
        if(
            diagnosticData.criticalRequestChains &&
            diagnosticData.criticalRequestChains.details &&
            diagnosticData.criticalRequestChains.details.chains &&
            Object.keys(diagnosticData.criticalRequestChains.details.chains).length > 0
        ) {
            const chains = diagnosticData.criticalRequestChains.details.chains;
            const renderChain = (chain, level = 0) => {
                const indent = '&nbsp;'.repeat(level * 4);
                const req = chain.request;
                let html = `
                    <tr>
                       <td style="padding: 8px; border-bottom: 1px solid #eee; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 300px;">
                            ${req.url ? `<a href="${req.url}" target="_blank" style="color: #1a73e8; text-decoration: none;">${req.url}</a>` : 'N/A'}
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #eee;">${req.transferSize || 0} bytes</td>
                        <td style="padding: 8px; border-bottom: 1px solid #eee;">${(req.endTime - req.startTime).toFixed(2) || 0} ms</td>
                    </tr>
                `;
        
                if (chain.children) {
                    Object.values(chain.children).forEach(child => {
                        html += renderChain(child, level + 1);
                    });
                }
        
                return html;
            };
        
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3 style="color: #d9534f;">
                        ${diagnosticData.criticalRequestChains.title || 'Critical Request Chains'}
                    </h3>
                    <div style="color: #555; font-size: 14px;">
                        ${diagnosticData.criticalRequestChains.description || ''}
                    </div>
                    <p style="font-size: 14px; color: #444;">
                        <strong>Longest Chain:</strong> ${diagnosticData.criticalRequestChains.details.longestChain.length} requests,
                        ${(diagnosticData.criticalRequestChains.details.longestChain.duration).toFixed(2)} ms,
                        ${diagnosticData.criticalRequestChains.details.longestChain.transferSize} bytes transferred.
                    </p>
                    <table style="width: 100%; max-width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Request URL</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Transfer Size</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Duration</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${Object.values(chains).map(chain => renderChain(chain)).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        //Long Tasks
        if ( diagnosticData.longTasks &&diagnosticData.longTasks.details && diagnosticData.longTasks.details.items.length > 0) 
        {
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3 style="color: #d9534f;">
                        ${diagnosticData.longTasks.title || 'Long Tasks'}
                        ${diagnosticData.longTasks.displayValue ? ' — ' + diagnosticData.longTasks.displayValue : ''}
                    </h3>
                    <div style="color: #555; font-size: 14px;">${diagnosticData.longTasks.description || ''}</div>
                    <table style="width: 100%; max-width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 14px; margin-top: 10px; word-wrap: break-word;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">URL</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Start Time (ms)</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Duration (ms)</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${diagnosticData.longTasks.details.items.map(item => `
                                <tr>
                                   <td style="padding: 8px; border-bottom: 1px solid #eee; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 300px;">
                                        ${item.url ? `<a href="${item.url}" target="_blank" style="color: #1a73e8;">${item.url}</a>` : 'N/A'}
                                    </td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${item.startTime.toFixed(2)}</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${item.duration.toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }
        
        // Render Blocking Resources
        if (
            diagnosticData['render-blocking-resources'] &&
            diagnosticData['render-blocking-resources'].details &&
            diagnosticData['render-blocking-resources'].details.items &&
            diagnosticData['render-blocking-resources'].details.items.length > 0
          ) {
            const renderBlocking = diagnosticData['render-blocking-resources'];
          
            reportContent += `
              <div style="margin-top: 20px;">
                <h3 style="color: #d9534f;">
                  ${renderBlocking.title || 'Render-Blocking Resources'}
                </h3>
                <div style="color: #555; font-size: 14px;">
                  ${renderBlocking.description || ''}
                </div>
                <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                  <thead>
                    <tr>
                      <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">URL</th>
                      <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Transfer Size</th>
                      <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Est. Savings</th>
                    </tr>
                  </thead>
                  <tbody>
                    ${renderBlocking.details.items.map(item => `
                      <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #eee;">
                          <a href="${item.url}" target="_blank" style="color: #007bff;">${item.url}</a>
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #eee;">
                          ${item.totalBytes} bytes
                        </td>
                        <td style="padding: 8px; border-bottom: 1px solid #eee;">
                          ${item.wastedMs} ms
                        </td>
                      </tr>
                    `).join('')}
                  </tbody>
                </table>
              </div>
            `;
          }          
        
        // Third-Party Resources
        if (diagnosticData.thirdPartySummary && typeof diagnosticData.thirdPartySummary === 'object') {
            const thirdPartyData = diagnosticData.thirdPartySummary;
        
            reportContent += '<div class="section" style="margin-top:20px;">';
        
            const thirdPartyTitle = thirdPartyData.title || 'Third-Party Resources';
            const thirdPartyDisplay = thirdPartyData.displayValue || '';
        
            reportContent += `<h3><strong>${thirdPartyTitle}</strong>${thirdPartyDisplay ? ` — ${thirdPartyDisplay}` : ''}</h3>`;
        
            if (thirdPartyData.description) {
                reportContent += `<p style="margin-bottom:12px;color:#555;">${thirdPartyData.description}</p>`;
            }
        
            const items = thirdPartyData.details?.items || [];
            const headings = thirdPartyData.details?.headings || [];
        
            if (items.length > 0 && headings.length > 0) {
                reportContent += '<table style="width:100%;table-layout:fixed;border-collapse:collapse;border:1px solid #ddd;margin-bottom:20px;">';
        
                // Table headers
                reportContent += '<thead><tr style="background:#eee;">';
                const columnWidth = (100 / headings.length).toFixed(2);
                headings.forEach(heading => {
                    reportContent += `<th style="padding:8px;border:1px solid #ccc;width:${columnWidth}%;word-break:break-word;">${heading.label || heading.key}</th>`;
                });
                reportContent += '</tr></thead><tbody>';
        
                // Rows with subItems
                items.forEach(item => {
                    const subItems = item.subItems?.items || [{}];
        
                    subItems.forEach((subItem, index) => {
                        reportContent += '<tr>';
        
                        headings.forEach(heading => {
                            const key = heading.key;
                            const subKey = heading.subItemsHeading?.key;
                            const subValueType = heading.subItemsHeading?.valueType || heading.valueType;
                            let value = '';
        
                            // For first row, show parent entity
                            if (index === 0 && item[key] !== undefined && !subKey) {
                                value = item[key];
                                if (subKey && subItem[subKey]) {
                                    const url = subItem[subKey];
                                    value += `<br><a href="${url}" target="_blank">${url}</a>`;
                                }
                            }
                            // For subItems
                            else if (subKey && subItem[subKey] !== undefined) {
                                const val = subItem[subKey];
                                switch (subValueType) {
                                    case 'url':
                                        value = `<a href="${val}" target="_blank">${val}</a>`;
                                        break;
                                    case 'bytes':
                                        value = typeof val === 'number' ? (val / 1024).toFixed(2) + ' KB' : 'N/A';
                                        break;
                                    case 'ms':
                                        value = typeof val === 'number' ? Math.round(val) + ' ms' : 'N/A';
                                        break;
                                    default:
                                        value = val || 'N/A';
                                }
                            }
                            // Fallback
                            else if (index === 0 && item[key] !== undefined) {
                                value = item[key];
                            } else {
                                value = '—';
                            }
        
                            reportContent += `<td style="padding:8px;border:1px solid #ccc;width:${columnWidth}%;word-break:break-word;">${value}</td>`;
                        });
        
                        reportContent += '</tr>';
                    });
                });
        
                reportContent += '</tbody></table>';
            } else {
                reportContent += '<p>No third-party resources found.</p>';
            }
        
            reportContent += '</div>';
        }        
         
        //Reduced CSS
        if (
            diagnosticData.unusedCssRules && diagnosticData.unusedCssRules.details && diagnosticData.unusedCssRules.details.items.length > 0) {
            const unusedCss = diagnosticData.unusedCssRules;
            const headings = unusedCss.details.headings || [];
            const items = unusedCss.details.items || [];
        
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3>
                        ${unusedCss.title || 'Unused CSS Rules'}
                        ${unusedCss.displayValue ? ' — ' + unusedCss.displayValue : ''}
                    </h3>
                    <div style="color: #555; font-size: 14px;">
                        ${unusedCss.description || ''}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                        <thead>
                            <tr>
                                ${headings.map(h => `<th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">${h.label || h.key}</th>`).join('')}
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => {
                                return `
                                    <tr>
                                        ${headings.map(heading => {
                                            const key = heading.key;
                                            const valueType = heading.valueType || 'text';
                                            let value = item[key];
        
                                            if (value === undefined) {
                                                value = '—';
                                            } else {
                                                switch (valueType) {
                                                    case 'url':
                                                        value = `<a href="${value}" target="_blank">${value}</a>`;
                                                        break;
                                                    case 'bytes':
                                                        value = (value / 1024).toFixed(1) + ' KiB';
                                                        break;
                                                    case 'ms':
                                                        value = Math.round(value) + ' ms';
                                                        break;
                                                    default:
                                                        value = value;
                                                }
                                            }
        
                                            return `<td style="padding: 8px; border-bottom: 1px solid #eee;">${value}</td>`;
                                        }).join('')}
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }        
        
        //Reduced Unused JavaScript
        if (
            diagnosticData.unusedJavascript &&
            diagnosticData.unusedJavascript.details &&
            diagnosticData.unusedJavascript.details.items &&
            diagnosticData.unusedJavascript.details.items.length > 0
        ) {
            const unusedJs = diagnosticData.unusedJavascript;
            const headings = unusedJs.details.headings || [];
            const items = unusedJs.details.items || [];
        
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3 style="color: #f0ad4e;">
                        ${unusedJs.title || 'Unused JavaScript'}
                        ${unusedJs.displayValue ? ` — ${unusedJs.displayValue}` : ''}
                    </h3>
                    <div style="color: #555; font-size: 14px;">${unusedJs.description || ''}</div>
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px; table-layout: fixed;">
                            <thead>
                                <tr>
                                    ${headings.map(h => `
                                        <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc; max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            ${h.label || h.key}
                                        </th>`).join('')}
                                </tr>
                            </thead>
                            <tbody>
                                ${items.map(item => {
                                    return `
                                        <tr>
                                            ${headings.map(heading => {
                                                const key = heading.key;
                                                const valueType = heading.valueType || 'text';
                                                let value = item[key];
        
                                                if (value === undefined) {
                                                    value = '—';
                                                } else {
                                                    switch (valueType) {
                                                        case 'url':
                                                            value = `<a href="${value}" target="_blank" style="color: #1a73e8; text-decoration: none;">${value}</a>`;
                                                            break;
                                                        case 'bytes':
                                                            value = (value / 1024).toFixed(1) + ' KiB';
                                                            break;
                                                        case 'ms':
                                                            value = Math.round(value) + ' ms';
                                                            break;
                                                        case 'code':
                                                            value = `<code style="font-family: monospace; background: #f9f9f9; padding: 2px 4px;">${value}</code>`;
                                                            break;
                                                    }
                                                }
        
                                                return `<td style="padding: 8px; border-bottom: 1px solid #eee; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 300px;">
                                                    ${value}
                                                </td>`;
                                            }).join('')}
                                        </tr>
                                    `;
                                }).join('')}
                            </tbody>
                        </table>
                    </div>
                </div>
            `;
        }                  


        if (diagnosticData.mainThreadWork?.details?.items?.length > 0) {
            const mainThreadMeta = diagnosticData.mainThreadWork;
        
            reportContent += '<div class="section" style="margin-top:20px;">';
            reportContent += `<h3 style="margin-bottom:10px;">
                ${mainThreadMeta.title || 'Main Thread Work Breakdown'}
                ${mainThreadMeta.displayValue ? ' — ' + mainThreadMeta.displayValue : ''}
            </h3>`;
        
            if (mainThreadMeta.description) {
                reportContent += `<p style="color:#555;margin-bottom:12px;">${mainThreadMeta.description}</p>`;
            }
        
            reportContent += '<table style="width:100%;border-collapse:collapse;border:1px solid #ddd;">';
            reportContent += `
                <thead>
                    <tr style="background:#eee;">
                        <th style="padding:8px;border:1px solid #ccc;">Category</th>
                        <th style="padding:8px;border:1px solid #ccc;">Time Spent</th>
                    </tr>
                </thead>
                <tbody>
            `;
        
            mainThreadMeta.details.items.forEach(item => {
                const groupLabel = item.groupLabel || 'Other';
                const duration = Math.round(item.duration || 0);
                reportContent += `
                    <tr>
                        <td style="padding:8px;border:1px solid #ccc;">${groupLabel}</td>
                        <td style="padding:8px;border:1px solid #ccc;">${duration} ms</td>
                    </tr>
                `;
            });
        
            reportContent += '</tbody></table></div>';
        }              
        
         //Uses Optimized Images
         if (
            diagnosticData.usesOptimizedImages &&
            diagnosticData.usesOptimizedImages.details &&
            diagnosticData.usesOptimizedImages.details.items &&
            diagnosticData.usesOptimizedImages.details.items.length > 0
        ) {
            const items = diagnosticData.usesOptimizedImages.details.items;
        
            reportContent += `
                <div style="margin-top: 20px;">
                    <h3 style="color: #d9534f;">
                        ${diagnosticData.usesOptimizedImages.title || 'Unoptimized Images'}
                        ${diagnosticData.usesOptimizedImages.displayValue ? ' — ' + diagnosticData.usesOptimizedImages.displayValue : ''}
                    </h3>
                    <div style="color: #555; font-size: 14px;">
                        ${diagnosticData.usesOptimizedImages.description || ''}
                    </div>
                    <table style="width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">URL</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Resource Size</th>
                                <th style="text-align: left; padding: 8px; border-bottom: 1px solid #ccc;">Est Savings</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${items.map(item => `
                                <tr>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">
                                        ${item.url ? `<a href="${item.url}" target="_blank" style="color: #1a73e8;">${item.url}</a>` : 'N/A'}
                                    </td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${(item.totalBytes / 1024).toFixed(2)} KB</td>
                                    <td style="padding: 8px; border-bottom: 1px solid #eee;">${(item.wastedBytes / 1024).toFixed(2)} KB</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        }

        reportContent += '</div>'; // Close the main container
    
        document.getElementById('coreVitalsData').innerHTML = reportContent;

        
        return reportContent;
    }

    document.getElementById("downloadReportBtn").addEventListener("click", async function () {
        const element = document.getElementById("coreVitalsData");

        if (!element || element.innerHTML.trim() === "") {
        alert("No content to export.");
        return;
        }

        const canvas = await html2canvas(element, {
        scale: 2,
        useCORS: true,
        scrollY: -window.scrollY
        });

        const imgData = canvas.toDataURL('image/jpeg', 1.0);

        const { jsPDF } = window.jspdf;
        const pdf = new jsPDF({
        orientation: 'portrait',
        unit: 'mm',
        format: 'a4'
        });

        const pageWidth = 210;
        const pageHeight = 297;
        const imgProps = pdf.getImageProperties(imgData);
        const imgWidth = pageWidth;
        const imgHeight = (imgProps.height * imgWidth) / imgProps.width;

        let heightLeft = imgHeight;
        let position = 0;

        // First page
        pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;

        while (heightLeft > 0) {
        position = heightLeft - imgHeight;
        pdf.addPage();
        pdf.addImage(imgData, 'JPEG', 0, position, imgWidth, imgHeight);
        heightLeft -= pageHeight;
        }

        // Construct file name from currentSelectedPage, strategy, and date
        const page = currentSelectedPage || "Page"; // Replace with your actual variable if different
        const strategy = currentSelectedStrategy || "Strategy"; // Replace with your actual strategy variable
        const date = new Date().toISOString().slice(0, 10); // e.g., 2025-05-22
        const fileName = `${page.replace(/\s+/g, '_')}_${strategy}_${date}.pdf`;
    
        pdf.save(fileName);
    });
    
});

document.addEventListener("DOMContentLoaded", function() {
    function switchType(newType) {
        const params = new URLSearchParams(window.location.search);
        params.set("type", newType);
        params.set("paged", 1); // Reset to page 1
        window.location.search = params.toString();
    }

    document.getElementById("toggle-urls").addEventListener("click", function() {
        switchType("urls");
    });

    document.getElementById("toggle-images").addEventListener("click", function() {
        switchType("images");
    });
});

//
// document.addEventListener('DOMContentLoaded', function () {
//     const loadMoreBtn = document.getElementById('loadMoreBrokenLinks');
//     const downloadBtn = document.getElementById('downloadBtn');

//     if (loadMoreBtn) {
//         loadMoreBtn.addEventListener('click', () => {
//             document.querySelectorAll('.broken-link-row[style*="display:none"]').forEach(row => {
//                 row.style.display = '';
//             });
//             loadMoreBtn.style.display = 'none';
//         });
//     }

//     if (downloadBtn) {
//         downloadBtn.addEventListener('click', () => {
//             const reportData = JSON.parse(downloadBtn.getAttribute('data-report'));
//             const type = downloadBtn.getAttribute('data-type');
//             const parentUrl = downloadBtn.getAttribute('data-parent-url');
//             let html = `
// <!DOCTYPE html>
// <html lang="en">
// <head>
//     <meta charset="UTF-8">
//     <title>Broken ${type === 'images' ? 'Images' : 'Links'} Report</title>
//     <style>
//         body { font-family: Arial; padding: 20px; }
//         h1 { color: #333; }
//         table { width: 100%; border-collapse: collapse; margin-top: 20px; }
//         th { background: #f5f5f5; text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
//         td { padding: 10px; border-bottom: 1px solid #eee; }
//         .status-error { background: #ffe5e5; color: red; padding: 5px 10px; border-radius: 15px; }
//         a { color: rgb(248, 8, 8); text-decoration: none; }
//     </style>
// </head>
// <body>
//     <h1>Broken ${type === 'images' ? 'Images' : 'Links'} Report</h1>
//     <p><strong>Date:</strong> ${new Date().toLocaleString()}</p>
//     <p><strong>Website:</strong> ${location.origin}</p>
//     <p><strong>Total:</strong> ${reportData.length}</p>
//     <table>
//         <thead>
//             <tr>
//                 <th>SN</th>
//                 <th>URL</th>
//                 <th>Parent URL</th>
//                 <th>Message</th>
//                 <th>Status Code</th>
//             </tr>
//         </thead>
//         <tbody>
// `;

//             reportData.forEach((link, i) => {
//                 html += `
// <tr>
//     <td>${i + 1}</td>
//     <td><a href="${link.url || ''}" target="_blank">${link.url || ''}</a></td>
//     <td><a href="${parentUrl}" target="_blank">${parentUrl}</a></td>
//     <td>${link.message || 'No message'}</td>
//     <td><span class="status-error">${link.status_code || 'N/A'}</span></td>
// </tr>`;
//             });

//             html += `
//         </tbody>
//     </table>
// </body>
// </html>`;

//             const blob = new Blob([html], { type: "text/html" });
//             const url = URL.createObjectURL(blob);
//             const a = document.createElement("a");
//             a.href = url;
//             a.download = `broken-links-report-${type}-${new Date().toISOString().slice(0,10)}.html`;
//             a.style.display = "none";
//             document.body.appendChild(a);
//             a.click();
//             document.body.removeChild(a);
//         });
//     }
// });

jQuery(document).ready(function ($) {
    let currentPage = 1;

    $('#loadMoreBrokenLinks').on('click', function () {
        currentPage++;

        $.ajax({
            url: qa_ajax_obj.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'load_more_broken_links',
                page: currentPage,
                type: qa_ajax_obj.type
            },
            success: function (response) {
                if (response.success && response.data.length > 0) {
                    response.data.forEach((link, index) => {
                        const row = `
                        <tr class="broken-link-row">
                            <td>${(currentPage - 1) * 10 + index + 1}</td>
                            <td><a href="${link.url}" target="_blank">${link.url}</a></td>
                            <td><a href="${link.parent_url}" target="_blank">${link.parent_url}</a></td>
                            <td>${link.message || 'No message'}</td>
                            <td><span class="status-error">${link.status_code || 'N/A'}</span></td>
                        </tr>`;
                        $('.qa-broken-links-table tbody').append(row);
                    });
                } else {
                    $('#loadMoreBrokenLinks').hide();
                }
            },
            error: function () {
                alert('Error loading more broken links.');
            }
        });
    });
});


// SiteHealth Accordian 
document.addEventListener('DOMContentLoaded', function () {
  const titles = document.querySelectorAll('.general-result');

  titles.forEach(title => {
    title.addEventListener('click', function () {
      const parent = this.closest('.accTabs');
      const content = parent.querySelector('.siteHAcc');
      content.classList.toggle('active');
      parent.classList.toggle('active');
    });
  });

  const fixButtons = document.querySelectorAll('.site-health-fix-btn');
  fixButtons.forEach(button => {
    button.addEventListener('click', function (event) {
      event.stopPropagation();
      event.preventDefault();
      showSiteHealthFixPopup();
    });
  });

  function showSiteHealthFixPopup() {
    if (document.getElementById('site-health-fix-popup-overlay')) {
      return;
    }

    const overlay = document.createElement('div');
    overlay.id = 'site-health-fix-popup-overlay';
    overlay.className = 'site-health-fix-popup-overlay';

    const popup = document.createElement('div');
    popup.className = 'site-health-fix-popup';

    const closeButton = document.createElement('button');
    closeButton.className = 'close-popup';
    closeButton.type = 'button';
    closeButton.textContent = '×';
    closeButton.addEventListener('click', function () {
      overlay.remove();
    });

    const heading = document.createElement('h2');
    heading.textContent = 'How to Fix';

    const paragraph = document.createElement('p');
    paragraph.textContent = 'This popup can be replaced with a full fix guide by the UI developer. For now, it shows example guidance text in a modal overlay.';

    popup.appendChild(closeButton);
    popup.appendChild(heading);
    popup.appendChild(paragraph);
    overlay.appendChild(popup);
    document.body.appendChild(overlay);
  }
});

// Automation Start
jQuery(document).ready(function($) {
    if (!$('#automation-settings-form').length) {
        return;
    }

    // Store initial state for auto-save
    let initialChecks = $('input.automation-check-item:checked').map(function() {
        return $(this).val();
    }).get().sort().join(',');

    let debounceTimer;
    let isProcessing = false;

    // Auto-save functionality when checkboxes change
    $('.automation-check-item').on('change', function() {
        clearTimeout(debounceTimer);
        
        debounceTimer = setTimeout(function() {
            let currentChecks = $('input.automation-check-item:checked').map(function() {
                return $(this).val();
            }).get().sort().join(',');

            // Only trigger if checks actually changed
            if (currentChecks !== initialChecks && !isProcessing) {
                isProcessing = true;
                updateCronSchedule();
                initialChecks = currentChecks;
            }
        }, 1000);
    });

    function updateCronSchedule() {
        let checkedBoxes = $('input.automation-check-item:checked');
        let frequency = $('select[name="automation_frequency"]').val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'sitechal_update_cron_schedule',
                enabled_checks: checkedBoxes.map(function() { return $(this).val(); }).get(),
                frequency: frequency,
                nonce: (window.sitechalAutomationData && window.sitechalAutomationData.cronNonce) ? window.sitechalAutomationData.cronNonce : ''
            },
            success: function(response) {
                if (response.success) {
                    showAutoSaveNotification('Automation schedule updated');
                }
                isProcessing = false;
            },
            error: function() {
                isProcessing = false;
            }
        });
    }

    function showAutoSaveNotification(message) {
        let notification = $('<div class="auto-save-notification">' + message + '</div>');
        notification.css({
            'position': 'fixed',
            'top': '32px',
            'right': '20px',
            'background': '#00a32a',
            'color': '#fff',
            'padding': '10px 20px',
            'border-radius': '4px',
            'z-index': '9999',
            'box-shadow': '0 2px 10px rgba(0,0,0,0.2)',
            'font-size': '14px',
            'opacity': '0',
            'transition': 'opacity 0.3s'
        });
        
        $('body').append(notification);
        
        setTimeout(function() {
            notification.css('opacity', '1');
        }, 10);
        
        setTimeout(function() {
            notification.css('opacity', '0');
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 2000);
    }

    // Premium feature click handler
    $('.check-item[data-premium="true"]').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        showPremiumPopup();
    });
});

function showPremiumPopup() {
    document.getElementById('premium-popup-overlay').classList.add('active');
}

function closePremiumPopup() {
    document.getElementById('premium-popup-overlay').classList.remove('active');
}

// Close popup when clicking outside
document.addEventListener('DOMContentLoaded', function() {
    var overlay = document.getElementById('premium-popup-overlay');
    if (overlay) {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closePremiumPopup();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay.classList.contains('active')) {
                closePremiumPopup();
            }
        });
    }
});

jQuery(document).ready(function($) {
        // Select All functionality
        $('#select-all-checks').on('change', function() {
            var isChecked = $(this).prop('checked');
            $('.automation-check-item').prop('checked', isChecked);
        });

        // Update Select All state when individual checkboxes change
        $('.automation-check-item').on('change', function() {
            var totalCheckboxes = $('.automation-check-item').length;
            var checkedCheckboxes = $('.automation-check-item:checked').length;
            
            $('#select-all-checks').prop('checked', totalCheckboxes === checkedCheckboxes);
        });

        // Initialize Select All state on page load
        var totalCheckboxes = $('.automation-check-item').length;
        var checkedCheckboxes = $('.automation-check-item:checked').length;
        $('#select-all-checks').prop('checked', totalCheckboxes === checkedCheckboxes && totalCheckboxes > 0);

        // Form validation before submission
        $('#automation-settings-form').on('submit', function(e) {
            var checkedBoxes = $('.automation-check-item:checked').length;
            
            if (checkedBoxes === 0) {
                e.preventDefault();
                
                // Show error message
                $('#validation-error').slideDown();
                
                // Scroll to error message
                $('html, body').animate({
                    scrollTop: $('#validation-error').offset().top - 100
                }, 500);
                
                // Hide error message after 5 seconds
                setTimeout(function() {
                    $('#validation-error').slideUp();
                }, 5000);
                
                return false;
            }
            
            // Hide error message if validation passes
            $('#validation-error').hide();
            return true;
        });

        // Hide error message when user checks a box
        $('.automation-check-item, #select-all-checks').on('change', function() {
            if ($('.automation-check-item:checked').length > 0) {
                $('#validation-error').slideUp();
            }
        });
    });
// Automation End




// Testimonial Read More Logic
document.addEventListener("DOMContentLoaded", function() {
    const testimonialTexts = document.querySelectorAll(".testimonial-text");
    testimonialTexts.forEach(p => {
        const fullText = p.getAttribute("data-fulltext");
        if (!fullText) return;
        
        const words = fullText.trim().split(/\s+/);
        if (words.length >30) {
            const truncatedText = words.slice(0, 30).join(" ") + "... ";
            p.innerHTML = truncatedText;
            
            const readMoreLink = document.createElement("a");
            readMoreLink.href = "https://wordpress.org/support/plugin/site-checker-all-in-one-qa-testing/reviews/"; 
            readMoreLink.className = "read-more-link";
            readMoreLink.target = "_blank";
            readMoreLink.innerText = "Read More...";
            
            p.appendChild(readMoreLink);
        }
    });
});
