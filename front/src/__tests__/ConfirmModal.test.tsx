import { render, screen, fireEvent } from '@testing-library/react';
import ConfirmModal from '@/components/ConfirmModal';
import '@testing-library/jest-dom';

/**
 * Tests du composant ConfirmModal.
 *
 * Ce composant est une boîte de dialogue de confirmation utilisée
 * pour les actions destructives (annuler une réservation, supprimer
 * une session, etc.). Les tests vérifient son affichage conditionnel,
 * ses interactions clavier/souris et ses valeurs par défaut.
 */
describe('ConfirmModal', () => {
  /**
   * Props par défaut utilisées dans la majorité des tests.
   * Les callbacks onConfirm et onCancel sont des mocks Jest
   * pour pouvoir vérifier qu'ils sont bien appelés.
   */
  const defaultProps = {
    open: true,
    title: 'Test Title',
    message: 'Test message',
    onConfirm: jest.fn(),
    onCancel: jest.fn(),
  };

  /**
   * Réinitialise les mocks avant chaque test pour éviter
   * que les appels d'un test précédent ne faussent les assertions.
   */
  beforeEach(() => {
    jest.clearAllMocks();
  });

  /**
   * Vérifie que le modal n'est pas rendu dans le DOM
   * quand la prop "open" est à false. C'est essentiel pour
   * éviter d'afficher un modal invisible mais présent.
   */
  it('should not render when open is false', () => {
    render(<ConfirmModal {...defaultProps} open={false} />);
    expect(screen.queryByText('Test Title')).not.toBeInTheDocument();
  });

  /**
   * Vérifie que le titre et le message sont bien affichés
   * lorsque le modal est ouvert. Ce sont les deux éléments
   * principaux que l'utilisateur doit voir.
   */
  it('should render title and message when open', () => {
    render(<ConfirmModal {...defaultProps} />);
    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('Test message')).toBeInTheDocument();
  });

  /**
   * Vérifie que le callback onConfirm est bien déclenché
   * (et une seule fois) lorsque l'utilisateur clique sur
   * le bouton de confirmation.
   */
  it('should call onConfirm when confirm button is clicked', () => {
    render(<ConfirmModal {...defaultProps} confirmText="Yes" />);
    fireEvent.click(screen.getByText('Yes'));
    expect(defaultProps.onConfirm).toHaveBeenCalledTimes(1);
  });

  /**
   * Vérifie que le callback onCancel est bien déclenché
   * lorsque l'utilisateur clique sur le bouton d'annulation.
   * Permet à l'utilisateur de fermer le modal sans agir.
   */
  it('should call onCancel when cancel button is clicked', () => {
    render(<ConfirmModal {...defaultProps} cancelText="No" />);
    fireEvent.click(screen.getByText('No'));
    expect(defaultProps.onCancel).toHaveBeenCalledTimes(1);
  });

  /**
   * Vérifie que le modal se ferme aussi via la touche Escape.
   * C'est une convention UX importante pour l'accessibilité :
   * tout modal doit pouvoir être fermé au clavier.
   */
  it('should call onCancel when Escape is pressed', () => {
    render(<ConfirmModal {...defaultProps} />);
    fireEvent.keyDown(window, { key: 'Escape' });
    expect(defaultProps.onCancel).toHaveBeenCalledTimes(1);
  });

  /**
   * Vérifie que les libellés des boutons utilisent les valeurs
   * par défaut en français ("Confirmer" et "Annuler") quand
   * les props confirmText et cancelText ne sont pas fournies.
   */
  it('should use default button texts if not provided', () => {
    render(<ConfirmModal {...defaultProps} />);
    expect(screen.getByText('Confirmer')).toBeInTheDocument();
    expect(screen.getByText('Annuler')).toBeInTheDocument();
  });
});