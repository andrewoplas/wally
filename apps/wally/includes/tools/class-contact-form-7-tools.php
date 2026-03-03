<?php
namespace Wally\Tools;

/**
 * Contact Form 7 management tools.
 *
 * Tools: list_contact_forms, get_contact_form, update_contact_form.
 * All tools require Contact Form 7 to be active (class_exists('WPCF7')).
 */

/**
 * List all Contact Form 7 forms.
 */
class ListContactForms extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WPCF7' );
	}

	public function get_name(): string {
		return 'list_contact_forms';
	}

	public function get_description(): string {
		return 'List all Contact Form 7 forms on the site. Returns form ID, title, and shortcode for embedding.';
	}

	public function get_category(): string {
		return 'forms';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'per_page' => [
					'type'        => 'integer',
					'description' => 'Number of forms to return (max 100).',
					'default'     => 20,
				],
				'page'     => [
					'type'        => 'integer',
					'description' => 'Page number for pagination.',
					'default'     => 1,
				],
			],
			'required'   => [],
		];
	}

	public function get_required_capability(): string {
		return 'wpcf7_edit_contact_forms';
	}

	public function execute( array $input ): array {
		$per_page = min( (int) ( $input['per_page'] ?? 20 ), 100 );
		$page     = max( (int) ( $input['page'] ?? 1 ), 1 );

		// WPCF7_ContactForm::find() supports $args array including per_page, offset.
		$args = [
			'per_page' => $per_page,
			'offset'   => ( $page - 1 ) * $per_page,
			'orderby'  => 'title',
			'order'    => 'ASC',
		];

		$forms = \WPCF7_ContactForm::find( $args );

		// Get total count.
		$total_args     = [ 'per_page' => -1 ];
		$all_forms      = \WPCF7_ContactForm::find( $total_args );
		$total          = count( $all_forms );

		$result = [];
		foreach ( $forms as $form ) {
			$result[] = [
				'id'        => $form->id(),
				'title'     => $form->title(),
				'shortcode' => '[contact-form-7 id="' . $form->id() . '" title="' . esc_attr( $form->title() ) . '"]',
			];
		}

		return [
			'forms'       => $result,
			'total'       => $total,
			'total_pages' => (int) ceil( $total / $per_page ),
			'page'        => $page,
			'per_page'    => $per_page,
		];
	}
}

/**
 * Get details of a single Contact Form 7 form.
 */
class GetContactForm extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WPCF7' );
	}

	public function get_name(): string {
		return 'get_contact_form';
	}

	public function get_description(): string {
		return 'Get details of a Contact Form 7 form by ID, including form template, mail settings, and recipient configuration.';
	}

	public function get_category(): string {
		return 'forms';
	}

	public function get_action(): string {
		return 'read';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id' => [
					'type'        => 'integer',
					'description' => 'The Contact Form 7 form ID (post ID).',
				],
			],
			'required'   => [ 'form_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'wpcf7_edit_contact_forms';
	}

	public function execute( array $input ): array {
		$form_id = absint( $input['form_id'] );
		$form    = \WPCF7_ContactForm::get_instance( $form_id );

		if ( ! $form ) {
			return [ 'error' => "Contact Form 7 form not found: {$form_id}" ];
		}

		$properties = $form->get_properties();
		$mail       = $properties['mail'] ?? [];

		return [
			'id'          => $form->id(),
			'title'       => $form->title(),
			'shortcode'   => '[contact-form-7 id="' . $form->id() . '" title="' . esc_attr( $form->title() ) . '"]',
			'form_body'   => $properties['form'] ?? '',
			'mail'        => [
				'subject'   => $mail['subject'] ?? '',
				'sender'    => $mail['sender'] ?? '',
				'recipient' => $mail['recipient'] ?? '',
				'body'      => $mail['body'] ?? '',
				'use_html'  => ! empty( $mail['use_html'] ),
			],
			'messages'    => $properties['messages'] ?? [],
		];
	}
}

/**
 * Update a Contact Form 7 form's title or mail settings.
 */
class UpdateContactForm extends ToolInterface {

	public static function can_register(): bool {
		return class_exists( 'WPCF7' );
	}

	public function get_name(): string {
		return 'update_contact_form';
	}

	public function get_description(): string {
		return 'Update a Contact Form 7 form\'s title, recipient email, mail subject, or mail body. Provide form_id and any fields to change.';
	}

	public function get_category(): string {
		return 'forms';
	}

	public function get_action(): string {
		return 'update';
	}

	public function get_parameters_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'form_id'         => [
					'type'        => 'integer',
					'description' => 'The Contact Form 7 form ID to update.',
				],
				'title'           => [
					'type'        => 'string',
					'description' => 'New form title.',
				],
				'mail_recipient'  => [
					'type'        => 'string',
					'description' => 'New email recipient address for form submissions.',
				],
				'mail_subject'    => [
					'type'        => 'string',
					'description' => 'New email subject line for form submissions.',
				],
				'mail_body'       => [
					'type'        => 'string',
					'description' => 'New email body template for form submissions. Can include [field-name] placeholders.',
				],
			],
			'required'   => [ 'form_id' ],
		];
	}

	public function get_required_capability(): string {
		return 'wpcf7_edit_contact_forms';
	}

	public function execute( array $input ): array {
		$form_id = absint( $input['form_id'] );
		$form    = \WPCF7_ContactForm::get_instance( $form_id );

		if ( ! $form ) {
			return [ 'error' => "Contact Form 7 form not found: {$form_id}" ];
		}

		$changes = [];

		if ( isset( $input['title'] ) ) {
			// CF7 uses wp_update_post internally when saving; title goes through set_title.
			$form->set_title( sanitize_text_field( $input['title'] ) );
			$changes[] = 'title';
		}

		// Update mail properties if any mail field is changing.
		if ( isset( $input['mail_recipient'] ) || isset( $input['mail_subject'] ) || isset( $input['mail_body'] ) ) {
			$properties = $form->get_properties();
			$mail       = $properties['mail'] ?? [];

			if ( isset( $input['mail_recipient'] ) ) {
				$mail['recipient'] = sanitize_email( $input['mail_recipient'] );
				$changes[]         = 'mail_recipient';
			}
			if ( isset( $input['mail_subject'] ) ) {
				$mail['subject'] = sanitize_text_field( $input['mail_subject'] );
				$changes[]       = 'mail_subject';
			}
			if ( isset( $input['mail_body'] ) ) {
				$mail['body'] = wp_kses_post( $input['mail_body'] );
				$changes[]    = 'mail_body';
			}

			$properties['mail'] = $mail;
			$form->set_properties( $properties );
		}

		if ( empty( $changes ) ) {
			return [ 'error' => 'No fields provided to update.' ];
		}

		$form->save();

		return [
			'form_id' => $form_id,
			'title'   => $form->title(),
			'updated' => $changes,
			'message' => "Contact Form 7 form \"{$form->title()}\" updated successfully.",
		];
	}
}
