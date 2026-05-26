import { render, screen, fireEvent } from '@testing-library/react';
import ConfirmModal from '@/components/ConfirmModal';
import '@testing-library/jest-dom';
describe('ConfirmModal', () => {
  const defaultProps = {
    open: true,
    title: 'Test Title',
    message: 'Test message',
    onConfirm: jest.fn(),
    onCancel: jest.fn(),
  };

  beforeEach(() => {
    jest.clearAllMocks();
  });

  it('should not render when open is false', () => {
    render(<ConfirmModal {...defaultProps} open={false} />);
    expect(screen.queryByText('Test Title')).not.toBeInTheDocument();
  });

  it('should render title and message when open', () => {
    render(<ConfirmModal {...defaultProps} />);
    expect(screen.getByText('Test Title')).toBeInTheDocument();
    expect(screen.getByText('Test message')).toBeInTheDocument();
  });

  it('should call onConfirm when confirm button is clicked', () => {
    render(<ConfirmModal {...defaultProps} confirmText="Yes" />);
    fireEvent.click(screen.getByText('Yes'));
    expect(defaultProps.onConfirm).toHaveBeenCalledTimes(1);
  });

  it('should call onCancel when cancel button is clicked', () => {
    render(<ConfirmModal {...defaultProps} cancelText="No" />);
    fireEvent.click(screen.getByText('No'));
    expect(defaultProps.onCancel).toHaveBeenCalledTimes(1);
  });

  it('should call onCancel when Escape is pressed', () => {
    render(<ConfirmModal {...defaultProps} />);
    fireEvent.keyDown(window, { key: 'Escape' });
    expect(defaultProps.onCancel).toHaveBeenCalledTimes(1);
  });

  it('should use default button texts if not provided', () => {
    render(<ConfirmModal {...defaultProps} />);
    expect(screen.getByText('Confirmer')).toBeInTheDocument();
    expect(screen.getByText('Annuler')).toBeInTheDocument();
  });
});