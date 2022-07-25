import QrScanner from './qr-scanner.min.js';

const txtQr = document.getElementById('qr-data');
const video = document.getElementById('video');

function afficherQr(resultat) {
	video.remove();
	txtQr.value = resultat;
	console.log("Qr code : "+resultat);
	document.forms['scanner_form'].submit();
}

export default function demarrer() {
	QrScanner.WORKER_PATH = 'resources/js/qr-scanner-worker.min.js';
	const qrScanner = new QrScanner(video, resultat => {
		afficherQr(resultat);
		qrScanner.stop();
	});

	qrScanner.start().catch(() => {
		if (!QrScanner.hasCamera()) erreur.appendChild(document.createTextNode("Aucune caméra détectée."));
	});
	return qrScanner;
}