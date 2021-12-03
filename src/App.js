import './styles/app.scss';
import Header from './components/Header';
import { Routes, Route } from 'react-router-dom';
import Presentation from './pages/Presentation';
import Inscription from './pages/Inscription';
import Concert from './images/concert.jpg';

function App() {
  return (
      <div className="App">
        <Header />
		<img src={Concert} alt="concert" />
        <p>My Virtual Wallet c’est plus qu’un simple Wallet.
		<br />
        Permettez à vos utilisateurs d’échanger une monnaie locale, décentralisée, personnalisée avec un système complet et sécurisé.</p>
		<Routes>
			<Route exact path="/" element={<App />} />
			<Route path="presentation" element={<Presentation />} />
			<Route path="inscription" element={<Inscription />} />
		</Routes>
      </div>
  );
}

export default App;
