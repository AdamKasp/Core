<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Sylius\Component\Core\Payment\Provider;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use SM\Factory\FactoryInterface as StateMachineFactoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Component\Core\Model\PaymentMethodInterface;
use Sylius\Component\Core\Payment\Provider\OrderPaymentProvider;
use Sylius\Component\Core\Payment\Provider\OrderPaymentProviderInterface;
use Sylius\Component\Payment\Factory\PaymentFactoryInterface;
use Sylius\Component\Payment\PaymentTransitions;
use Sylius\Component\Payment\Resolver\DefaultPaymentMethodResolverInterface;
use Sylius\Component\Resource\StateMachine\StateMachineInterface;

/**
 * @author Mateusz Zalewski <mateusz.zalewski@lakion.com>
 */
final class OrderPaymentProviderSpec extends ObjectBehavior
{
    function let(
        DefaultPaymentMethodResolverInterface $defaultPaymentMethodResolver,
        PaymentFactoryInterface $paymentFactory,
        StateMachineFactoryInterface $stateMachineFactory
    ) {
        $this->beConstructedWith(
            $defaultPaymentMethodResolver,
            $paymentFactory,
            $stateMachineFactory,
            PaymentInterface::STATE_NEW
        );
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(OrderPaymentProvider::class);
    }

    function it_implements_order_payment_provider_interface()
    {
        $this->shouldImplement(OrderPaymentProviderInterface::class);
    }

    function it_provides_payment_in_configured_state_with_payment_method_from_last_cancelled_payment(
        OrderInterface $order,
        PaymentFactoryInterface $paymentFactory,
        PaymentInterface $lastCancelledPayment,
        PaymentInterface $newPayment,
        PaymentMethodInterface $paymentMethod,
        StateMachineFactoryInterface $stateMachineFactory,
        StateMachineInterface $stateMachine
    ) {
        $order->getTotal()->willReturn(1000);
        $order->getCurrencyCode()->willReturn('USD');
        $order->getLastPayment(PaymentInterface::STATE_CANCELLED)->willReturn($lastCancelledPayment);

        $lastCancelledPayment->getMethod()->willReturn($paymentMethod);

        $paymentFactory->createWithAmountAndCurrencyCode(1000, 'USD')->willReturn($newPayment);

        $newPayment->setMethod($paymentMethod)->shouldBeCalled();
        $newPayment->getState()->willReturn(PaymentInterface::STATE_CART);
        $newPayment->setOrder($order)->shouldBeCalled();

        $stateMachineFactory->get($newPayment, PaymentTransitions::GRAPH)->willReturn($stateMachine);
        $stateMachine->getTransitionToState(PaymentInterface::STATE_NEW)->willReturn(PaymentTransitions::TRANSITION_CREATE);
        $stateMachine->apply(PaymentTransitions::TRANSITION_CREATE)->shouldBeCalled();

        $this->provideOrderPayment($order)->shouldReturn($newPayment);
    }

    function it_provides_payment_in_configured_state_with_payment_method_from_last_failed_payment(
        OrderInterface $order,
        PaymentFactoryInterface $paymentFactory,
        PaymentInterface $lastFailedPayment,
        PaymentInterface $newPayment,
        PaymentMethodInterface $paymentMethod,
        StateMachineFactoryInterface $stateMachineFactory,
        StateMachineInterface $stateMachine
    ) {
        $order->getTotal()->willReturn(1000);
        $order->getCurrencyCode()->willReturn('USD');
        $order->getLastPayment(PaymentInterface::STATE_CANCELLED)->willReturn(null);
        $order->getLastPayment(PaymentInterface::STATE_FAILED)->willReturn($lastFailedPayment);

        $lastFailedPayment->getMethod()->willReturn($paymentMethod);

        $paymentFactory->createWithAmountAndCurrencyCode(1000, 'USD')->willReturn($newPayment);

        $newPayment->setMethod($paymentMethod)->shouldBeCalled();
        $newPayment->getState()->willReturn(PaymentInterface::STATE_CART);
        $newPayment->setOrder($order)->shouldBeCalled();

        $stateMachineFactory->get($newPayment, PaymentTransitions::GRAPH)->willReturn($stateMachine);
        $stateMachine->getTransitionToState(PaymentInterface::STATE_NEW)->willReturn(PaymentTransitions::TRANSITION_CREATE);
        $stateMachine->apply(PaymentTransitions::TRANSITION_CREATE)->shouldBeCalled();

        $this->provideOrderPayment($order)->shouldReturn($newPayment);
    }

    function it_provides_payment_in_configured_state_with_default_payment_method(
        DefaultPaymentMethodResolverInterface $defaultPaymentMethodResolver,
        OrderInterface $order,
        PaymentFactoryInterface $paymentFactory,
        PaymentInterface $newPayment,
        PaymentMethodInterface $paymentMethod,
        StateMachineFactoryInterface $stateMachineFactory,
        StateMachineInterface $stateMachine
    ) {
        $order->getTotal()->willReturn(1000);
        $order->getCurrencyCode()->willReturn('USD');
        $order->getLastPayment(PaymentInterface::STATE_CANCELLED)->willReturn(null);
        $order->getLastPayment(PaymentInterface::STATE_FAILED)->willReturn(null);

        $paymentFactory->createWithAmountAndCurrencyCode(1000, 'USD')->willReturn($newPayment);
        $newPayment->setOrder($order)->shouldBeCalled();

        $defaultPaymentMethodResolver->getDefaultPaymentMethod($newPayment)->willReturn($paymentMethod);

        $newPayment->setMethod($paymentMethod)->shouldBeCalled();
        $newPayment->getState()->willReturn(PaymentInterface::STATE_CART);

        $stateMachineFactory->get($newPayment, PaymentTransitions::GRAPH)->willReturn($stateMachine);
        $stateMachine->getTransitionToState(PaymentInterface::STATE_NEW)->willReturn(PaymentTransitions::TRANSITION_CREATE);
        $stateMachine->apply(PaymentTransitions::TRANSITION_CREATE)->shouldBeCalled();

        $this->provideOrderPayment($order)->shouldReturn($newPayment);
    }

    function it_does_not_apply_any_transition_if_target_state_is_the_same_as_new_payment(
        DefaultPaymentMethodResolverInterface $defaultPaymentMethodResolver,
        OrderInterface $order,
        PaymentFactoryInterface $paymentFactory,
        PaymentInterface $newPayment,
        PaymentMethodInterface $paymentMethod,
        StateMachineFactoryInterface $stateMachineFactory
    ) {
        $this->beConstructedWith(
            $defaultPaymentMethodResolver,
            $paymentFactory,
            $stateMachineFactory,
            PaymentInterface::STATE_CART
        );

        $order->getTotal()->willReturn(1000);
        $order->getCurrencyCode()->willReturn('USD');
        $order->getLastPayment(PaymentInterface::STATE_CANCELLED)->willReturn(null);
        $order->getLastPayment(PaymentInterface::STATE_FAILED)->willReturn(null);

        $paymentFactory->createWithAmountAndCurrencyCode(1000, 'USD')->willReturn($newPayment);
        $newPayment->setOrder($order)->shouldBeCalled();

        $defaultPaymentMethodResolver->getDefaultPaymentMethod($newPayment)->willReturn($paymentMethod);

        $newPayment->setMethod($paymentMethod)->shouldBeCalled();
        $newPayment->getState()->willReturn(PaymentInterface::STATE_CART);

        $stateMachineFactory->get(Argument::any())->shouldNotBeCalled();

        $this->provideOrderPayment($order)->shouldReturn($newPayment);
    }
}
