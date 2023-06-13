require 'sinatra'
require 'json'

imaginary_installed_software = {
    'plugins' => {
        'some-plugin-slug' => '3.2.1'
    },
    'themes' => {
        'some-theme-slug' => '30.3'
    },
    'wordpresses' => [
        '6.2'
    ]
}

post '/webhook' do
    data = JSON.parse(request.body.read, symbolize_names: true)

    puts data

    detected_vulnerable_software = []

    # Check if one of the installed extensions (plugins & themes) is vulnerable
    ['plugins', 'themes'].each do |type|
        imaginary_installed_software[type].each do |slug, version|
            # Skip if the extension is not in the webhook data
            next unless data[type.to_sym] && data[type.to_sym][slug.to_sym]

            extension = data[type.to_sym][slug.to_sym]

            if (extension[:fixed_in].nil? || Gem::Version.new(extension[:fixed_in]) > Gem::Version.new(version)) &&
               (extension[:introduced_in].nil? || Gem::Version.new(extension[:introduced_in]) <= Gem::Version.new(version))
                detected_vulnerable_software << {
                    'type' => type,
                    'slug' => slug,
                    'version' => version
                }
            end
        end
    end

    # Check if one of the installed WordPress versions is vulnerable
    imaginary_installed_software['wordpresses'].each do |version|
        # Skip version if it is not in the webhook data
        next unless data[:wordpresses] && data[:wordpresses][version.to_sym]

        wordpress = data[:wordpresses][version.to_sym]

        if (wordpress[:fixed_in].nil? || Gem::Version.new(wordpress[:fixed_in]) > Gem::Version.new(version)) &&
           (wordpress[:introduced_in].nil? || Gem::Version.new(wordpress[:introduced_in]) <= Gem::Version.new(version))
            detected_vulnerable_software << {
                'type' => 'wordpress',
                'version' => version
            }
        end
    end

    # Process the results somehow - we're sending it to the server console for now.
    puts "Vulnerable software detected: #{detected_vulnerable_software.inspect}"
end
